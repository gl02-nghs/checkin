<?php
/**
 * 檔案名稱: login.php
 * 功能: 處理 Google OAuth 登入並初始化使用者權限
 */

require_once 'config.php';
require_once 'vendor/autoload.php';

session_start();

// 建立 Google 客戶端並載入 config.php 的設定
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");

// 取得資料庫連線
$db = getDbConnection();

// --- 處理 Google 登入回傳 ---
if (isset($_GET['code'])) {
    try {
        // 取得 Access Token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        if (isset($token['error'])) {
            throw new Exception("Google 授權失敗: " . $token['error_description']);
        }
        
        $client->setAccessToken($token['access_token']);

        // 取得 Google 使用者資訊
        $google_oauth = new Google_Service_Oauth2($client);
        $google_account_info = $google_oauth->userinfo->get();
        
        $email = $google_account_info->email;
        $name = $google_account_info->name;

        // 檢查使用者是否已存在資料庫
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // 新使用者：判斷是否為超級管理員 (Email 比對)
            $role = ($email === SUPER_ADMIN_EMAIL) ? 'super_admin' : 'user';
            
            $insert = $db->prepare("INSERT INTO users (email, name, role) VALUES (?, ?, ?)");
            $insert->execute([$email, $name, $role]);
            
            // 重新取得完整的使用者資料
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        }

        // 將使用者資訊存入 Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        // 登入成功，導向至首頁
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// 如果尚未登入，產生登入 URL
$authUrl = $client->createAuthUrl();
?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>學校報到系統 - 登入</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md w-96 text-center">
        <h1 class="text-2xl font-bold mb-6 text-blue-800">校園報到系統</h1>
        
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <p class="mb-6 text-gray-600">請使用學校 Google 帳號登入</p>
        
        <a href="<?php echo $authUrl; ?>" class="flex items-center justify-center bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold py-2 px-4 rounded-lg shadow-sm transition duration-150">
			<img src=ngshsignin.png alt="Google帳號登入">
        </a>
    </div>
</body>
</html>