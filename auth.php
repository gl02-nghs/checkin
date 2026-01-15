<?php
/**
 * 檔案名稱: auth.php
 * 功能: 處理 Google OAuth 回傳，支援新使用者自動註冊
 */

require_once 'config.php';
require_once 'vendor/autoload.php';

// 此處依賴 Cookie 紀錄登入狀態，不需 session_start
$db = getDbConnection();

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_CHECKIN_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");

// 取得活動 ID (從 state 參數傳遞)
$activityId = isset($_GET['state']) ? intval($_GET['state']) : 0;

// --- 核心邏輯: 處理 Google 回傳的 Code ---
if (isset($_GET['code'])) {
    try {
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if (isset($token['error'])) {
            throw new Exception("Google 授權失敗");
        }
        $client->setAccessToken($token['access_token']);

        $google_oauth = new Google_Service_Oauth2($client);
        $info = $google_oauth->userinfo->get();
        $email = $info->email;
        $name = $info->name;

        // 1. 檢查資料庫是否有此人員
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $tokenStr = "";

        if ($user) {
            // 已存在使用者：檢查並取得現有 Token
            $tokenStr = $user['remember_token'];
            if (empty($tokenStr)) {
                $tokenStr = bin2hex(random_bytes(32));
                $db->prepare("UPDATE users SET remember_token = ? WHERE id = ?")
                   ->execute([$tokenStr, $user['id']]);
            }
        } else {
            // 2. 新使用者：自動註冊進入 users 資料表
            $tokenStr = bin2hex(random_bytes(32)); // 產生新 Token
            
            $sql = "INSERT INTO users (email, name, role, remember_token) VALUES (?, ?, 'user', ?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$email, $name, $tokenStr]);
            
            // 註冊成功後不需額外動作，後續統一寫入 Cookie
        }

        // 3. 寫入 Cookie (有效期 30 天)
        setcookie('remember_token', $tokenStr, time() + (86400 * 30), "/");

        // 4. 成功後，導回到簽到頁面 (checkin.php) 並帶上活動 ID
        if ($activityId > 0) {
            header("Location: signin.php?id=" . $activityId);
        } else {
            // 若無活動 ID 則導回首頁或登入頁
            display_error("未指定活動ID或指定錯誤ID");
        }
        exit;

    } catch (Exception $e) {
        // 使用自定義錯誤訊息避免直接暴露系統資訊
        error_log("Auth Error: " . $e->getMessage());
        die("驗證過程發生錯誤，請稍後再試。");
    }
} else {
    // --- 若沒有 code: 主動發起 Google 授權 ---
    if ($activityId > 0) {
        $client->setState((string)$activityId);
        $authUrl = $client->createAuthUrl();
        header("Location: " . $authUrl);
        exit;
    } else {
        // 如果連活動 ID 都沒有，視為異常進入
        //header("Location: login.php");
        display_error("未指定活動ID");
        exit;
    }
}