<?php
// --- 渲染邏輯 ---
$mode = $_GET['mode'] ?? '';
// 判斷是否要進入手寫畫面
$isHandwrite = ($mode === 'handwrite');
// 判斷是否要顯示「調用簽名」畫面
$hasStoredSignature = (!empty($user['signature_base64']) && !$isHandwrite);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>簽到模組20260115</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
	<link rel="stylesheet" href="signin.css?v=<?php echo filemtime('signin.css'); ?>">
</head>
<body class="bg-slate-100 overflow-hidden">

<?php if ($isHandwrite): ?>
   <!-- 使用 fixed inset-0 強制佔滿整個手機畫面 -->
    <div id="handwrite-container" class="fixed inset-0 flex flex-col bg-white overflow-hidden overscroll-none">
        
        <!-- 簽名區域: 真正的 flex-1，確保佔據除了底欄外的所有空間 -->
        <div id="canvas-wrapper" class="flex-1 relative bg-white min-h-0 w-full overflow-hidden">
            <!-- touch-none 重要：防止繪圖時頁面跟著捲動 -->
            <canvas id="canvas" class="w-full h-full block touch-none cursor-crosshair"></canvas>
            
            <!-- 提示文字 -->
            <div id="instruction-text" class="absolute inset-0 flex items-center justify-center pointer-events-none z-0 transition-opacity duration-500">
                <p class="text-5xl text-slate-400 tracking-widest select-none opacity-30" 
                   style="writing-mode: vertical-lr; text-orientation: sideways; letter-spacing: 0.8rem;">
                    請在此橫向簽名
                </p>
            </div>
        </div>

        <!-- 下方按鈕列: 強制貼在最底部 -->
        <div class="h-24 bg-slate-50 border-t flex items-center justify-between px-8 flex-shrink-0 safe-area-bottom">
            <button type="button" onclick="clearCanvas()" class="text-slate-500 font-bold p-4">清除重寫</button>
            <button type="button" onclick="showSaveModal()" class="bg-blue-600 text-white px-10 py-3 rounded-xl font-bold shadow-lg">完成簽名</button>
        </div>
    </div>

    <!-- 儲存詢問 Modal -->
    <div id="save-modal" class="fixed inset-0 bg-black/60 z-50 hidden items-center justify-center p-6 backdrop-blur-sm">
        <div class="bg-white rounded-3xl p-8 max-w-xs w-full text-center shadow-2xl">
            <div class="text-3xl mb-4">💾</div>
            <p class="text-lg font-bold mb-6 text-slate-800">是否要將此簽名保存，以後可以為您調用簽名使用?</p>
            <div class="flex gap-3">
                <button type="button" onclick="submitSignature(false)" class="flex-1 bg-slate-100 py-4 rounded-xl font-bold text-slate-600">否</button>
                <button type="button" onclick="submitSignature(true)" class="flex-1 bg-blue-600 text-white py-4 rounded-xl font-bold">是</button>
            </div>
        </div>
    </div>

    <!-- 隱藏表單 -->
    <form id="checkin-form" method="POST" class="hidden">
        <input type="hidden" name="action" value="do_checkin">
        <input type="hidden" name="signature_data" id="sig-input">
        <input type="hidden" name="save_to_profile" id="save-input" value="0">
    </form>

    <style>
        /* 處理 iPhone 底部白條區域 */
        .safe-area-bottom {
            padding-bottom: env(safe-area-inset-bottom);
            height: calc(6rem + env(safe-area-inset-bottom));
        }
        /* 禁止整個 Body 滾動，防止手寫時畫面亂跳 */
        body {
            position: fixed;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
    </style>

    <script>
        const canvas = document.getElementById('canvas');
        const wrapper = document.getElementById('canvas-wrapper');
        const instruction = document.getElementById('instruction-text');
        const ctx = canvas.getContext('2d');
        let isDrawing = false;

        function resizeCanvas() {
            const rect = wrapper.getBoundingClientRect();
            const dpr = window.devicePixelRatio || 1;
            canvas.width = rect.width * dpr;
            canvas.height = rect.height * dpr;
            canvas.style.width = rect.width + 'px';
            canvas.style.height = rect.height + 'px';
            ctx.scale(dpr, dpr);
			//畫筆粗細度
            ctx.lineWidth = 6;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#0f172a';
        }

        function getPos(e) {
            const rect = canvas.getBoundingClientRect();
            const t = e.touches ? e.touches[0] : e;
            return { x: t.clientX - rect.left, y: t.clientY - rect.top };
        }

        // 新增：隱藏提示文字的函式
        function hideInstruction() {
            if (instruction) {
                instruction.style.opacity = '0';
                // 延遲一段時間後徹底移除或設為 pointer-events-none，避免佔用佈局（雖然這裡已經是 absolute）
                setTimeout(() => {
                    instruction.classList.add('hidden');
                }, 500); 
            }
        }

        canvas.addEventListener('touchstart', (e) => {
            isDrawing = true;
            hideInstruction(); // 開始簽名時隱藏
            ctx.beginPath();
            const p = getPos(e);
            ctx.moveTo(p.x, p.y);
            e.preventDefault();
        }, { passive: false });

        canvas.addEventListener('mousedown', (e) => {
            isDrawing = true;
            hideInstruction(); // 開始簽名時隱藏
            ctx.beginPath();
            const p = getPos(e);
            ctx.moveTo(p.x, p.y);
        });

        canvas.addEventListener('touchmove', (e) => {
            if (!isDrawing) return;
            const p = getPos(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            e.preventDefault();
        }, { passive: false });

        canvas.addEventListener('mousemove', (e) => {
            if (!isDrawing) return;
            const p = getPos(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
        });

        window.addEventListener('touchend', () => isDrawing = false);
        window.addEventListener('mouseup', () => isDrawing = false);

        function clearCanvas() { 
            ctx.clearRect(0, 0, canvas.width, canvas.height); 
            // 如果希望清除後提示文字重新出現，可以取消註解下一行
            // instruction.classList.remove('hidden');
            // setTimeout(() => instruction.style.opacity = '1', 10);
        }

        function showSaveModal() { document.getElementById('save-modal').classList.replace('hidden', 'flex'); }
        function hideSaveModal() { document.getElementById('save-modal').classList.replace('flex', 'hidden'); }
        
        function submitSignature(save) {
            document.getElementById('save-input').value = save ? "1" : "0";
            document.getElementById('sig-input').value = canvas.toDataURL('image/png');
            document.getElementById('checkin-form').submit();
        }

        window.onload = resizeCanvas;
        window.onresize = resizeCanvas;
    </script>

<?php elseif ($hasStoredSignature): ?>
    <!-- 情形：有 Cookie 且驗證成功，從 users 取出簽名 -->
<div class="h-screen flex flex-col bg-slate-50 overflow-hidden">
    <!-- 頂部標題 -->
    <div class="h-16 bg-white border-b flex items-center justify-center px-6 shrink-0 shadow-sm z-10">
        <h2 class="text-xl font-black text-slate-900 text-center line-clamp-1 tracking-tight">
            <?php echo htmlspecialchars($activity['title']); ?>
        </h2>
    </div>

    <!-- 主要內容區 -->
    <div class="flex-1 flex flex-col items-center justify-start p-4 pb-8">
        
        <!-- 姓名展示區 -->
        <div class="w-full text-center mt-2">
            <p class="text-slate-500 text-xs mb-0">簽到姓名</p>
            <h1 class="text-5xl font-black text-slate-900 leading-none tracking-tighter truncate px-2">
                <?php echo mb_substr($user['name'], 0, 5, 'UTF-8'); ?>
            </h1>
        </div>

        <!-- 簽名預覽區：這裡移除了 max-w 限制，並使用 flex-1 讓它自動佔據中間剩餘空間 -->
		<div class="w-full flex flex-col items-center justify-start my-4">
			<p class="text-slate-400 text-[10px] mb-1">預覽簽名</p>
			
			<!-- 容器層 -->
			<div class="w-full max-w-[95%] h-56 bg-white border-2 border-dashed border-slate-200 rounded-2xl flex items-center justify-center overflow-hidden shadow-inner relative">
				
				<?php if (!empty($user['signature_base64'])): ?>
					<!-- 
						核心邏輯說明：
						1. absolute: 絕對定位，中心點對齊。
						2. w-[56vw] (或固定數值): 關鍵在於將圖片的寬度設為容器的「高度」。
						3. h-[95vw] (或固定數值): 將圖片的高度設為容器的「寬度」。
						4. rotate-90: 執行旋轉。
						5. scale: 視需要微調放大率以填補邊緣。
					-->
					<img src="<?php echo $user['signature_base64']; ?>" 
						 style="
							width: 14rem; /* 對應父層的 h-56 (224px) */
							height: 95%;  /* 對應父層的寬度百分比 */
							position: absolute;
							object-fit: contain;
							transform: rotate(-90deg) scale(1.5);
							transform-origin: center;
						 "
						 class="transition-transform duration-300">
				<?php else: ?>
					<span class="text-slate-300 text-sm font-medium">尚無簽名資料</span>
				<?php endif; ?>
				
			</div>
		</div>


        <!-- 操作按鈕區：這裡保持 max-w-xs 以維持美觀 -->
        <div class="w-full max-w-xs space-y-2 shrink-0">
            <form method="POST" class="m-0">
                <input type="hidden" name="action" value="do_checkin">
                <input type="hidden" name="signature_data" value="<?php echo htmlspecialchars($user['signature_base64']); ?>">
                <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-xl font-bold text-xl shadow-lg active:scale-95 transition-transform">
                    確認並完成簽到
                </button>
            </form>
            
            <div class="grid grid-cols-2 gap-2">
                <button onclick="logout()" class="bg-white border border-slate-200 text-slate-600 py-3 rounded-xl font-bold text-sm shadow-sm active:bg-slate-50">
                    重新驗證
                </button>
                <a href="?id=<?php echo $activityId; ?>&mode=handwrite" class="bg-white border border-slate-200 text-slate-600 py-3 rounded-xl font-bold text-sm text-center flex items-center justify-center shadow-sm active:bg-slate-50">
                    重新簽名
                </a>
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
        ctx.lineWidth = 6;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1e293b';
		ctx.shadowColor = '#1e293b'; // 陰影顏色與筆跡相同
        ctx.shadowBlur = 1.2;   // 模糊程度
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