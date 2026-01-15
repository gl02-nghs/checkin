<?php
/**
 * 檔案名稱: index.php
 * 功能: 系統入口網頁，根據使用者權限顯示功能選單
 */

require_once 'config.php';
session_start();

// 檢查是否已登入，若未登入則導回登入頁面
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role'];
$userName = $_SESSION['name'];
$userEmail = $_SESSION['email'];

?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>校園報到系統 - 首頁</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- 導航欄 -->
    <nav class="bg-blue-800 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <i class="fas fa-school text-2xl mr-2"></i>
                    <span class="font-bold text-xl tracking-tight">校園報到系統</span>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-sm">
                        <span class="block font-medium"><?php echo htmlspecialchars($userName); ?></span>
                        <span class="block text-blue-200 text-xs"><?php echo htmlspecialchars($userEmail); ?></span>
                    </div>
                    <a href="logout.php" class="bg-blue-700 hover:bg-blue-600 px-3 py-2 rounded-md text-sm font-medium transition">
                        登出
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- 主要內容區 -->
    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800">您好，歡迎回來！</h2>
            <p class="text-gray-600">目前的權限身分：
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                    <?php 
                        if($role === 'super_admin') echo '超級管理員';
                        elseif($role === 'admin') echo '一般管理員';
                        else echo '一般使用者';
                    ?>
                </span>
            </p>
        </div>

        <!-- 功能卡片網格 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- 所有身分共同功能：個人簽到紀錄 -->
            <div class="bg-white overflow-hidden shadow rounded-lg border-t-4 border-blue-500 hover:shadow-md transition">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-history text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">我的簽到紀錄</h3>
                            <p class="text-sm text-gray-500">查看您過去參加的所有活動</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-3">
                    <a href="my_history.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">進入查看 &rarr;</a>
                </div>
            </div>

            <?php if ($role === 'admin' || $role === 'super_admin'): ?>
            <!-- 管理員功能：管理活動 -->
            <div class="bg-white overflow-hidden shadow rounded-lg border-t-4 border-purple-500 hover:shadow-md transition">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-calendar-check text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">活動管理</h3>
                            <p class="text-sm text-gray-500">建立、編輯與檢視簽到名單</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-3">
                    <a href="admin_activities.php" class="text-purple-600 hover:text-purple-800 text-sm font-medium">進入管理 &rarr;</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($role === 'super_admin'): ?>
            <!-- 超級管理員專屬：帳號權限管理 -->
            <div class="bg-white overflow-hidden shadow rounded-lg border-t-4 border-red-500 hover:shadow-md transition">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-users-cog text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">系統帳號管理</h3>
                            <p class="text-sm text-gray-500">切換人員權限或手動新增帳號</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-6 py-3">
                    <a href="super_admin_users.php" class="text-red-600 hover:text-red-800 text-sm font-medium">進入設定 &rarr;</a>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <footer class="mt-auto py-6 text-center text-gray-400 text-sm">
        &copy; <?php echo date('Y'); ?> 台北市立南港高中. All rights reserved.
    </footer>

</body>
</html>