<?php
/**
 * 檔案名稱: checkin_result.php
 * 功能: 顯示簽到成功或失敗的視覺回饋
 */
$status = $_GET['status'] ?? 'error';
$title = $_GET['title'] ?? '活動';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>簽到結果 - 校園報到系統</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">

    <div class="bg-white rounded-3xl shadow-2xl shadow-gray-200 p-10 max-w-sm w-full text-center border border-gray-100">
        <?php if ($status === 'success'): ?>
            <!-- 成功狀態 -->
            <div class="w-24 h-24 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check fa-3x"></i>
            </div>
            <h1 class="text-2xl font-black text-gray-800 mb-2">簽到成功！</h1>
            <p class="text-gray-500 text-sm mb-6">
                您已完成「<span class="font-bold text-gray-700"><?php echo htmlspecialchars($title); ?></span>」的報到手續。
            </p>
            <div class="py-4 border-t border-dashed border-gray-100">
                <p class="text-xs text-gray-400">現在可以安全關閉此瀏覽器視窗</p>
            </div>
        <?php else: ?>
            <!-- 失敗狀態 -->
            <div class="w-24 h-24 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-times fa-3x"></i>
            </div>
            <h1 class="text-2xl font-black text-gray-800 mb-2">簽到失敗</h1>
            <p class="text-gray-500 text-sm mb-8">發生了未知的錯誤，或是連結已失效。請重新掃描 QR Code 或洽詢現場工作人員。</p>
            <a href="index.php" class="inline-block bg-gray-900 text-white px-8 py-3 rounded-xl font-bold shadow-lg transition transform active:scale-95">返回首頁</a>
        <?php endif; ?>
    </div>

</body>
</html>