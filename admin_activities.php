<?php
/**
 * 檔案名稱: admin_activities.php
 * 功能: 管理員建立與管理簽到活動，包含時間排程與自動狀態判定
 */

require_once 'config.php';
session_start();

// 權限檢查：管理員或超級管理員
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header('Location: login.php');
    exit;
}

$db = getDbConnection();
$message = '';
$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];

// --- 處理 POST 動作：新增活動 ---
if (isset($_POST['action']) && $_POST['action'] === 'add_activity') {
    $title = trim($_POST['title']);
    
    // 組合時間格式
    $start_time = $_POST['start_date'] . ' ' . $_POST['start_hour'] . ':' . $_POST['start_min'] . ':00';
    $end_time = $_POST['end_date'] . ' ' . $_POST['end_hour'] . ':' . $_POST['end_min'] . ':00';
    
    $early_minutes = intval($_POST['early_minutes']);
    $late_minutes = intval($_POST['late_minutes']);
    
    try {
        $stmt = $db->prepare("INSERT INTO activities (creator_id, title, start_time, end_time, early_minutes, late_minutes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $title, $start_time, $end_time, $early_minutes, $late_minutes]);
        $message = "成功：已建立活動「" . htmlspecialchars($title) . "」";
    } catch (PDOException $e) {
        $message = "錯誤：無法建立活動。" . $e->getMessage();
    }
}

/**
 * 輔助函式：判定活動目前的自動狀態
 */
function getActivityStatus($act) {
    $now = time();
    $start_ts = strtotime($act['start_time']);
    $end_ts = strtotime($act['end_time']);
    
    $checkin_open = $start_ts - ($act['early_minutes'] * 60);
    $checkin_close = $end_ts + ($act['late_minutes'] * 60);
    
    if ($now < $checkin_open) {
        return ['label' => '尚未開始', 'class' => 'bg-yellow-100 text-yellow-800', 'sort' => 2];
    }
    if ($now <= $checkin_close) {
        return ['label' => '進行中', 'class' => 'bg-green-100 text-green-800', 'sort' => 1];
    }
    return ['label' => '已結束', 'class' => 'bg-gray-100 text-gray-800', 'sort' => 3];
}

// 取得活動原始清單
if ($role === 'super_admin') {
    $query = "SELECT a.*, u.name as creator_name 
              FROM activities a 
              JOIN users u ON a.creator_id = u.id 
              ORDER BY a.start_time DESC";
    $raw_activities = $db->query($query)->fetchAll();
} else {
    $stmt = $db->prepare("SELECT *, '' as creator_name FROM activities WHERE creator_id = ? ORDER BY start_time DESC");
    $stmt->execute([$userId]);
    $raw_activities = $stmt->fetchAll();
}

$activities = [];
foreach ($raw_activities as $act) {
    $act['status_info'] = getActivityStatus($act);
    $activities[] = $act;
}

usort($activities, function($a, $b) {
    if ($a['status_info']['sort'] !== $b['status_info']['sort']) {
        return $a['status_info']['sort'] <=> $b['status_info']['sort'];
    }
    return strtotime($b['start_time']) <=> strtotime($a['start_time']);
});

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$baseUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>活動管理 - 校園報到系統</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen pb-12">

    <nav class="bg-purple-800 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="index.php" class="mr-4 hover:text-purple-200 transition"><i class="fas fa-arrow-left"></i></a>
                    <span class="font-bold text-xl tracking-tight">活動管理中心</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-xs bg-purple-700 px-2 py-1 rounded">
                        <?php echo $role === 'super_admin' ? '超級管理員' : '一般管理員'; ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-md <?php echo strpos($message, '成功') !== false ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400'; ?> shadow-sm">
            <i class="fas <?php echo strpos($message, '成功') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- 建立新活動表單 -->
        <div class="bg-white shadow-sm rounded-xl mb-10 overflow-hidden border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center">
                <i class="fas fa-calendar-plus text-purple-600 mr-2"></i>
                <h3 class="text-lg font-semibold text-gray-800">建立排程簽到活動</h3>
            </div>
            <form action="admin_activities.php" method="POST" class="p-6 space-y-6">
                <input type="hidden" name="action" value="add_activity">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">活動主題名稱</label>
                    <input type="text" name="title" required placeholder="例如：112學年度第一次校務會議" class="block w-full border border-gray-300 rounded-lg shadow-sm p-2.5 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- 開始時間 -->
                    <div class="bg-blue-50/30 p-4 rounded-lg border border-blue-100">
                        <label class="block text-sm font-bold text-blue-800 mb-2">活動開始時段</label>
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <input type="date" name="start_date" id="start_date" required class="block w-full border border-gray-300 rounded-lg p-2 focus:ring-blue-500" onchange="syncEndDate(this.value)">
                            <div class="flex space-x-1">
                                <select name="start_hour" id="start_hour" class="block w-full border border-gray-300 rounded-lg p-2" onchange="syncEndHour(this.value)">
                                    <?php for($i=0; $i<24; $i++) printf('<option value="%02d">%02d時</option>', $i, $i); ?>
                                </select>
                                <select name="start_min" id="start_min" class="block w-full border border-gray-300 rounded-lg p-2">
                                    <option value="00">00分</option>
                                    <option value="15">15分</option>
                                    <option value="30">30分</option>
                                    <option value="45">45分</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- 結束時間 -->
                    <div class="bg-orange-50/30 p-4 rounded-lg border border-orange-100">
                        <label class="block text-sm font-bold text-orange-800 mb-2">活動預計結束時段</label>
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <input type="date" name="end_date" id="end_date" required class="block w-full border border-gray-300 rounded-lg p-2 focus:ring-orange-500">
                            <div class="flex space-x-1">
                                <select name="end_hour" id="end_hour" class="block w-full border border-gray-300 rounded-lg p-2">
                                    <?php for($i=0; $i<24; $i++) printf('<option value="%02d">%02d時</option>', $i, $i); ?>
                                </select>
                                <select name="end_min" id="end_min" class="block w-full border border-gray-300 rounded-lg p-2">
                                    <option value="00">00分</option>
                                    <option value="15">15分</option>
                                    <option value="30">30分</option>
                                    <option value="45">45分</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-50 pt-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            提早簽到緩衝 <span class="text-gray-400 font-normal">(分鐘)</span>
                        </label>
                        <input type="number" name="early_minutes" value="15" min="0" required class="block w-full border border-gray-300 rounded-lg shadow-sm p-2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            遲延簽到緩衝 <span class="text-gray-400 font-normal">(分鐘)</span>
                        </label>
                        <input type="number" name="late_minutes" value="15" min="0" required class="block w-full border border-gray-300 rounded-lg shadow-sm p-2.5">
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-10 rounded-lg transition shadow-md">
                        建立並發布活動
                    </button>
                </div>
            </form>
        </div>

        <!-- 活動列表 -->
        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">目前活動排程</h3>
                <span class="text-xs text-gray-500">自動排序：進行中優先</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">活動主題</th>
                            <?php if($role === 'super_admin'): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">建立人員</th>
                            <?php endif; ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">活動時間</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">狀態</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">管理動作</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php foreach ($activities as $act): 
                            $status = $act['status_info'];
                        ?>
                        <tr class="<?php echo $status['sort'] === 1 ? 'bg-green-50/30' : ''; ?> hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($act['title']); ?></div>
                            </td>
                            <?php if($role === 'super_admin'): ?>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span class="bg-gray-100 px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($act['creator_name']); ?></span>
                            </td>
                            <?php endif; ?>
                            <td class="px-6 py-4">
                                <div class="text-xs text-gray-600 mb-1">
                                    <span class="inline-block w-4 text-blue-500 font-bold">起</span> <?php echo date('Y/m/d H:i', strtotime($act['start_time'])); ?>
                                </div>
                                <div class="text-xs text-gray-600">
                                    <span class="inline-block w-4 text-orange-500 font-bold">迄</span> <?php echo date('Y/m/d H:i', strtotime($act['end_time'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full <?php echo $status['class']; ?>">
                                    <?php echo $status['label']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center space-x-4">
                                    <a href="view_results.php?id=<?php echo $act['id']; ?>" class="text-blue-600 hover:text-blue-800 p-2 hover:bg-blue-50 rounded-lg">
                                        <i class="fas fa-clipboard-list fa-lg"></i>
                                    </a>
                                    <?php if($status['sort'] <= 2): ?>
                                    <button onclick="showQRCode('<?php echo $baseUrl . '/checkin.php?id=' . $act['id']; ?>', '<?php echo htmlspecialchars($act['title']); ?>')" class="text-indigo-600 hover:text-indigo-800 p-2 hover:bg-indigo-50 rounded-lg">
                                        <i class="fas fa-qrcode fa-lg"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- QR Code Modal -->
    <div id="qr-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4 z-50 transition-all">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-8 text-center transform scale-95 transition-transform">
            <div class="mb-4 flex justify-between items-center">
                <h3 id="qr-title" class="text-lg font-bold text-gray-800 truncate pr-4"></h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            <div id="qr-container" class="mb-6 p-4 bg-white inline-block border border-gray-100 rounded-xl shadow-inner">
                <img id="qr-image" src="" alt="QR Code" class="mx-auto">
            </div>
            <div class="bg-gray-50 rounded-lg p-3 mb-6 text-left">
                <p class="text-[10px] text-gray-400 uppercase font-bold tracking-widest mb-1">簽到網址</p>
                <p id="qr-url-text" class="text-xs text-gray-500 break-all font-mono"></p>
            </div>
            <button onclick="closeModal()" class="w-full bg-gray-900 hover:bg-black text-white font-bold py-3 rounded-xl transition shadow-lg">
                確認並關閉
            </button>
        </div>
    </div>

    <script>
        // 自動帶入日期邏輯
        function syncEndDate(val) {
            document.getElementById('end_date').value = val;
        }
        
        // 自動帶入小時邏輯 (結束時間預設比開始時間晚一小時)
        function syncEndHour(val) {
            let nextHour = (parseInt(val) + 1) % 24;
            let formattedHour = nextHour.toString().padStart(2, '0');
            document.getElementById('end_hour').value = formattedHour;
        }

        function showQRCode(url, title) {
            document.getElementById('qr-title').innerText = title;
            document.getElementById('qr-url-text').innerText = url;
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(url)}&margin=10`;
            document.getElementById('qr-image').src = qrUrl;
            
            const modal = document.getElementById('qr-modal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('div').classList.remove('scale-95');
                modal.querySelector('div').classList.add('scale-100');
            }, 10);
        }

        function closeModal() {
            const modal = document.getElementById('qr-modal');
            modal.querySelector('div').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 150);
        }

        document.getElementById('qr-modal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        // 初始化預設日期為今天
        window.onload = function() {
            let today = new Date().toISOString().split('T')[0];
            document.getElementById('start_date').value = today;
            document.getElementById('end_date').value = today;
        };
    </script>

</body>
</html>