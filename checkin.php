<?php
/**
 * 檔案名稱: checkin.php
 * 功能: 修正無限循環驗證問題，並落實 Protocol 所有 UI 規範
 */

require_once 'config.php';

// 1. 基本驗證: 讀取 $_GET['id']
$activityId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($activityId <= 0) {
    display_error("錯誤：未指定有效的活動 ID。");
}

$db = getDbConnection();
$stmt = $db->prepare("SELECT * FROM activities WHERE id = ?");
$stmt->execute([$activityId]);
$activity = $stmt->fetch();

if (!$activity) {
    display_error("錯誤：查無此活動資訊。");
}

// 2. 環境排除: LINE 偵測 (優先執行)
$userAgent = $_SERVER['HTTP_USER_AGENT'];
if (strpos($userAgent, 'Line') !== false) {
    display_line_warning();
}

// 3. 驗證邏輯: 檢查載具 Cookie (注意：名稱必須與 auth.php 一致)
$userToken = $_COOKIE['remember_token'] ?? ''; // 這裡修正為與 auth.php 相同的 remember_token
$user = null;

if ($userToken) {
    // 檢查資料庫內是否有人員的 token 相符
    $stmt = $db->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$userToken]);
    $user = $stmt->fetch();
}

// 4. 沒有 Cookie 或驗證失敗：跳轉至驗證網頁
if (!$user) {
    // 使用 state 參數打包活動 id 回來
    $authUri = GOOGLE_CHECKIN_REDIRECT_URI . "?state=" . $activityId;
    header("Location: " . $authUri);
    exit;
}

// 5. 特別情形判定: 重複簽到
$stmt = $db->prepare("SELECT id FROM attendance WHERE activity_id = ? AND identifier = ?");
$stmt->execute([$activityId, $user['email']]);
if ($stmt->fetch()) {
    display_error("您已經完成 [" . htmlspecialchars($activity['title']) . "] 簽到", "info");
}

// 6. 特別情形判定: 未在時限
$now = time();
$startTime = strtotime($activity['start_time']);
$endTime = strtotime($activity['end_time']);
$earlyMinutes = intval($activity['early_minutes'] ?? 0);
$lastMinutes = intval($activity['last_minutes'] ?? 0);

$allowStart = $startTime - ($earlyMinutes * 60);
$allowEnd = $endTime + ($lastMinutes * 60);

if ($now < $allowStart) {
    display_error("本次活動尚未開始。<br><small>正式開始時間：" . date('Y-m-d H:i', $startTime) . "</small>", "info");
}
if ($now > $allowEnd) {
    display_error("本次活動已經結束。<br><small>正式結束時間：" . date('Y-m-d H:i', $endTime) . "</small>", "info");
}

// 7. 處理寫入行為 (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'do_checkin') {
        $sigData = $_POST['signature_data'] ?? '';
        $saveToProfile = ($_POST['save_to_profile'] ?? '0') === '1';

        if (!empty($sigData)) {
            // 寫入 attendance 表
            $ins = $db->prepare("INSERT INTO attendance (activity_id, identifier, signature_path, checkin_time) VALUES (?, ?, ?, NOW())");
            $ins->execute([$activityId, $user['email'], $sigData]);

            // 如果同意保存簽名
            if ($saveToProfile) {
                $upd = $db->prepare("UPDATE users SET signature_base64 = ? WHERE id = ?");
                $upd->execute([$sigData, $user['id']]);
            }
            display_success("簽到成功！");
        }
    }

    if ($action === 'clear_auth') {
        setcookie('remember_token', '', time() - 3600, "/");
        header("Location: checkin.php?id=" . $activityId);
        exit;
    }
}

// --- 渲染邏輯 ---
$mode = $_GET['mode'] ?? '';
// 判斷是否要進入手寫畫面
$isHandwrite = ($mode === 'handwrite');
// 判斷是否要顯示「調用簽名」畫面
$hasStoredSignature = (!empty($user['signature_base64']) && !$isHandwrite);

// UI 輔助函式
function display_error($msg, $type = "error") {
    $color = ($type == "error") ? "text-red-600" : "text-blue-600";
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><script src='https://cdn.tailwindcss.com'></script></head><body class='bg-slate-50 flex items-center justify-center min-h-screen p-6'><div class='bg-white p-8 rounded-3xl shadow-xl w-full max-w-sm text-center'><div class='text-4xl mb-4'>" . ($type == "error" ? "⚠️" : "ℹ️") . "</div><div class='text-lg font-bold $color'>$msg</div></div></body></html>";
    exit;
}

function display_success($msg) {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><script src='https://cdn.tailwindcss.com'></script></head><body class='bg-slate-50 flex items-center justify-center min-h-screen p-6'><div class='bg-white p-8 rounded-3xl shadow-xl w-full max-w-sm text-center'><div class='text-5xl mb-4'>✅</div><div class='text-xl font-bold text-slate-800'>$msg</div></div></body></html>";
    exit;
}

function display_line_warning() {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><script src='https://cdn.tailwindcss.com'></script></head><body class='bg-slate-50 flex items-center justify-center min-h-screen p-6'><div class='bg-white p-8 rounded-3xl shadow-xl w-full max-w-sm text-center'><div class='text-4xl mb-4'>🚫</div><h2 class='text-lg font-bold text-red-600 mb-4'>不支援 LINE 內建瀏覽器</h2><p class='text-sm text-slate-600 mb-6 leading-relaxed'>本系統無法使用 LINE 內建瀏覽器簽到，請務必使用您載具的外部瀏覽器 (如 Chrome、Safari) 簽到。</p><a href='https://help.line.me/line/smartphone?lang=zh-Hant&contentId=20023875' target='_blank' class='text-blue-500 underline text-sm'>LINE 官方設定文件連結</a></div></body></html>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>簽到模組</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .sig-rotate { transform: rotate(-90deg); }
        #canvas { touch-action: none; background: #fff; cursor: crosshair; }
    </style>
</head>
<body class="bg-slate-100 overflow-hidden">

<?php if ($isHandwrite): ?>
    <!-- 手寫畫面: 90% 以上手寫區域 -->
    <div class="h-screen w-screen flex flex-col bg-white">
        <div class="flex-1 relative">
            <canvas id="canvas" class="w-full h-full"></canvas>
            <div class="absolute top-1/2 left-4 -translate-y-1/2 pointer-events-none opacity-10">
                <p class="text-4xl font-bold rotate-90">請在此橫向簽名</p>
            </div>
        </div>
        <div class="h-24 bg-slate-50 border-t flex items-center justify-between px-8">
            <button type="button" onclick="clearCanvas()" class="text-slate-500 font-bold">清除重寫</button>
            <button type="button" onclick="showSaveModal()" class="bg-blue-600 text-white px-10 py-3 rounded-xl font-bold shadow-lg">完成簽名</button>
        </div>
    </div>

    <!-- 儲存詢問 Modal (頁面內，非 Alert) -->
    <div id="save-modal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-6">
        <div class="bg-white rounded-3xl p-8 max-w-xs w-full text-center">
            <div class="text-3xl mb-4">💾</div>
            <p class="text-lg font-bold mb-6">是否要將此簽名保存，以後可以為您調用簽名使用?</p>
            <div class="flex gap-3">
                <button onclick="submitSignature(false)" class="flex-1 bg-slate-100 py-3 rounded-xl font-bold">否</button>
                <button onclick="submitSignature(true)" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-bold">是</button>
            </div>
        </div>
    </div>

    <form id="checkin-form" method="POST">
        <input type="hidden" name="action" value="do_checkin">
        <input type="hidden" name="signature_data" id="sig-input">
        <input type="hidden" name="save_to_profile" id="save-input" value="0">
    </form>

<?php elseif ($hasStoredSignature): ?>
    <!-- 情形：有 Cookie 且驗證成功，從 users 取出簽名 -->
    <div class="h-screen flex flex-col">
        <div class="h-1/6 bg-white border-b flex items-center justify-center px-4 overflow-hidden">
            <h2 class="text-lg font-bold text-slate-800 text-center"><?php echo htmlspecialchars($activity['title']); ?></h2>
        </div>
        <div class="flex-1 flex flex-col items-center justify-center p-6 space-y-6">
            <div class="w-full text-center">
                <h1 class="text-7xl font-black text-slate-900 leading-none tracking-tighter w-full truncate">
                    <?php echo mb_substr($user['name'], 0, 5, 'UTF-8'); ?>
                </h1>
            </div>
            <!-- 資料庫簽名圖轉 -90 度 -->
            <div class="w-64 h-64 bg-white border-2 border-dashed border-slate-200 rounded-3xl flex items-center justify-center overflow-hidden">
                <img src="<?php echo $user['signature_base64']; ?>" class="sig-rotate w-full h-full object-contain p-4">
            </div>
            <div class="w-full max-w-xs space-y-3">
                <form method="POST">
                    <input type="hidden" name="action" value="do_checkin">
                    <input type="hidden" name="signature_data" value="<?php echo htmlspecialchars($user['signature_base64']); ?>">
                    <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold text-xl shadow-xl active:scale-95 transition-transform">以此簽名完成簽到</button>
                </form>
                <div class="grid grid-cols-2 gap-3">
                    <button onclick="logout()" class="bg-slate-200 text-slate-600 py-3 rounded-xl font-bold">重新驗證</button>
                    <a href="?id=<?php echo $activityId; ?>&mode=handwrite" class="bg-slate-200 text-slate-600 py-3 rounded-xl font-bold text-center flex items-center justify-center">重新簽名</a>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- 驗證成功但無簽名紀錄 (或是剛從 auth 跳回來) -->
    <div class="h-screen flex flex-col">
        <div class="h-1/6 bg-white border-b flex items-center justify-center px-4 overflow-hidden">
            <h2 class="text-lg font-bold text-slate-800 text-center"><?php echo htmlspecialchars($activity['title']); ?></h2>
        </div>
        <div class="flex-1 flex flex-col items-center justify-center p-6 space-y-8">
            <div class="w-full text-center">
                <h1 class="text-7xl font-black text-slate-900 leading-none tracking-tighter w-full truncate">
                    <?php echo mb_substr($user['name'], 0, 5, 'UTF-8'); ?>
                </h1>
                <p class="text-slate-400 mt-2 font-medium"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <div class="w-full max-w-xs space-y-4">
                <a href="?id=<?php echo $activityId; ?>&mode=handwrite" class="block w-full bg-blue-600 text-white py-5 rounded-2xl font-bold text-2xl text-center shadow-xl active:scale-95 transition-transform">手寫簽到</a>
                <button onclick="logout()" class="w-full bg-slate-200 text-slate-600 py-4 rounded-xl font-bold">重新驗證</button>
            </div>
        </div>
    </div>
<?php endif; ?>

<form id="logout-form" method="POST" class="hidden">
    <input type="hidden" name="action" value="clear_auth">
</form>

<script>
    function logout() {
        document.getElementById('logout-form').submit();
    }

    <?php if ($isHandwrite): ?>
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');
    let drawing = false;

    function initCanvas() {
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
        ctx.lineWidth = 5;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#000';
    }

    window.addEventListener('resize', initCanvas);
    initCanvas();

    function getXY(e) {
        const rect = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        return { x: clientX - rect.left, y: clientY - rect.top };
    }

    function startDraw(e) {
        drawing = true;
        const {x, y} = getXY(e);
        ctx.beginPath();
        ctx.moveTo(x, y);
        if(e.cancelable) e.preventDefault();
    }

    function moveDraw(e) {
        if (!drawing) return;
        const {x, y} = getXY(e);
        ctx.lineTo(x, y);
        ctx.stroke();
        if(e.cancelable) e.preventDefault();
    }

    function endDraw() { drawing = false; }

    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', moveDraw);
    window.addEventListener('mouseup', endDraw);
    canvas.addEventListener('touchstart', startDraw, {passive: false});
    canvas.addEventListener('touchmove', moveDraw, {passive: false});
    canvas.addEventListener('touchend', endDraw);

    function clearCanvas() { ctx.clearRect(0, 0, canvas.width, canvas.height); }

    function showSaveModal() {
        const data = canvas.toDataURL();
        if (data.length < 2000) return; // 防止空簽名
        document.getElementById('save-modal').classList.replace('hidden', 'flex');
    }

    function submitSignature(save) {
        const btn = event.target;
        btn.disabled = true; // 防範重複提交
        document.getElementById('sig-input').value = canvas.toDataURL();
        document.getElementById('save-input').value = save ? "1" : "0";
        document.getElementById('checkin-form').submit();
    }
    <?php endif; ?>
</script>
</body>
</html>