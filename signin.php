<?php
/*
簽到頁面功能(2026-1-15版本)
此版本後將都採用微調方式處理，避免AI擅自改動代碼內容造成原有內容遺失或遭到破壞
*/
opcache_reset();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
//載入設定檔
require_once 'config.php';

//載入函數
require_once 'func.php';

//初始與基本驗證: 讀取 $_GET['id'] LINE內建瀏覽偵測
require_once 'init.php';

//處理寫入行為 (POST)
require_once 'action.php';

//輸出網頁
include 'signin_html.php';

?>
