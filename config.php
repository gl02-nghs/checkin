<?php
/**
 * 檔案名稱: config.php
 * 功能: 儲存系統全域設定、資料庫連線及 Google OAuth 憑證
 */

// --- 資料庫設定 ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'school_checkin');
define('DB_USER', 'root');
define('DB_PASS', 'fgroewfdjlkjlks22jflksd');

// --- Google OAuth 設定 ---
define('GOOGLE_CLIENT_ID', '683975015751-fp8543fglsklj5409ugfdlkjhj72.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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J');
define('GOOGLE_REDIRECT_URI', 'https://sys.nksh.tp.edu.tw/checkin/login.php');
// 簽到頁面專用的 Redirect URI (避免 mismatch 並區隔功能)
define('GOOGLE_CHECKIN_REDIRECT_URI', 'https://sys.nksh.tp.edu.tw/checkin/auth.php');
// --- 系統管理員設定 ---
define('SUPER_ADMIN_EMAIL', 'gl02@nksh.tp.edu.tw');

/**
 * 取得資料庫連線物件 (PDO)
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        die("資料庫連線失敗: " . $e->getMessage());
    }
}