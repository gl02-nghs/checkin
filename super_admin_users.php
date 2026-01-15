<?php
/**
 * 檔案名稱: super_admin_users.php
 * 功能: 超級管理員專用的帳號管理頁面
 */

require_once 'config.php';
session_start();

// 權限檢查：僅限超級管理員
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: index.php');
    exit;
}

$db = getDbConnection();
$message = '';

// --- 處理 POST 動作：新增帳號 ---
if (isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $new_email = trim($_POST['email']);
    $new_name = trim($_POST['name']);
    $new_role = $_POST['role'];

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $message = "錯誤：無效的 Email 格式。";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO users (email, name, role) VALUES (?, ?, ?)");
            $stmt->execute([$new_email, $new_name, $new_role]);
            $message = "成功：已新增帳號 $new_email";
        } catch (PDOException $e) {
            $message = "錯誤：該帳號可能已存在。";
        }
    }
}

// --- 處理 POST 動作：變更權限 ---
if (isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $target_id = $_POST['user_id'];
    $target_role = $_POST['role'];
    $target_email = $_POST['user_email'];

    // 防止自己改掉自己的超級管理員權限
    if ($target_email === SUPER_ADMIN_EMAIL) {
        $message = "錯誤：不能變更原始超級管理員的權限。";
    } else {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$target_role, $target_id]);
        $message = "成功：已更新使用者權限。";
    }
}

// 取得所有使用者清單
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系統帳號管理 - 校園報到系統</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

    <nav class="bg-red-800 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="index.php" class="mr-4 hover:text-red-200"><i class="fas fa-arrow-left"></i></a>
                    <span class="font-bold text-xl tracking-tight">系統帳號管理</span>
                </div>
                <div class="text-sm">
                    超級管理員模式
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        
        <?php if ($message): ?>
        <div class="mb-4 p-4 rounded-md <?php echo strpos($message, '成功') !== false ? 'bg-green-100 text-green-700 border border-green-400' : 'bg-red-100 text-red-700 border border-red-400'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- 新增帳號表單 -->
        <div class="bg-white shadow rounded-lg mb-10 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-medium text-gray-900">手動新增 Google 帳號</h3>
            </div>
            <form action="super_admin_users.php" method="POST" class="p-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="hidden" name="action" value="add_user">
                <div>
                    <label class="block text-sm font-medium text-gray-700">姓名</label>
                    <input type="text" name="name" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email (Google)</label>
                    <input type="email" name="email" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-red-500 focus:border-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">身分權限</label>
                    <select name="role" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-red-500 focus:border-red-500">
                        <option value="user">一般使用者</option>
                        <option value="admin">一般管理者</option>
                        <option value="super_admin">超級管理者</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition">
                        新增帳號
                    </button>
                </div>
            </form>
        </div>

        <!-- 使用者清單 -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">全系統人員名單</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">人員資訊</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">目前權限</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">權限變更</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">註冊日期</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($u['name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($u['email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php 
                                        if($u['role'] === 'super_admin') echo 'bg-red-100 text-red-800';
                                        elseif($u['role'] === 'admin') echo 'bg-purple-100 text-purple-800';
                                        else echo 'bg-gray-100 text-gray-800';
                                    ?>">
                                    <?php echo $u['role']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($u['email'] !== SUPER_ADMIN_EMAIL): ?>
                                <form action="super_admin_users.php" method="POST" class="flex items-center space-x-2">
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="user_email" value="<?php echo $u['email']; ?>">
                                    <select name="role" class="text-sm border border-gray-300 rounded p-1">
                                        <option value="user" <?php if($u['role'] === 'user') echo 'selected'; ?>>設為使用者</option>
                                        <option value="admin" <?php if($u['role'] === 'admin') echo 'selected'; ?>>設為管理者</option>
                                        <option value="super_admin" <?php if($u['role'] === 'super_admin') echo 'selected'; ?>>設為超管</option>
                                    </select>
                                    <button type="submit" class="text-blue-600 hover:text-blue-900 text-sm">更新</button>
                                </form>
                                <?php else: ?>
                                <span class="text-xs text-gray-400 italic">系統創始者 (不可更動)</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('Y-m-d', strtotime($u['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>