<?php
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

$userAgent = $_SERVER['HTTP_USER_AGENT'];
if (strpos($userAgent, 'Line') !== false) {
    display_line_warning();
}

//檢查載具 Cookie
$userToken = $_COOKIE['remember_token'] ?? '';
$user = null;

if ($userToken) {
    // 檢查資料庫內是否有人員的 token 相符
    $stmt = $db->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$userToken]);
    $user = $stmt->fetch();
}

//沒有 Cookie 或驗證失敗：跳轉至驗證網頁
if (!$user) {
    // 使用 state 參數打包活動 id 回來
    $authUri = GOOGLE_CHECKIN_REDIRECT_URI . "?state=" . $activityId;
    header("Location: " . $authUri);
    exit;
}

//重複簽到
$stmt = $db->prepare("SELECT id, checkin_time FROM attendance WHERE activity_id = ? AND identifier = ?");
$stmt->execute([$activityId, $user['email']]);
$attendanceRecord = $stmt->fetch(PDO::FETCH_ASSOC);
if ($attendanceRecord) {
    $checkinTime = $attendanceRecord['checkin_time'];
	$checkinTime = "<span class='text-blue-600 font-mono font-bold'>" . $checkinTime . "</span>";
	$showtitle = "<span class='text-emerald-500 font-mono font-bold text-xl block mt-2'>" . htmlspecialchars($activity['title']) . "</span>";
    display_success(
        "完成簽到時間<BR>" . $checkinTime . "<BR><BR>" . $showtitle, 
        "info"
    );
}


//未在時間區簽到
$now = time();
$startTime = strtotime($activity['start_time']);
$endTime = strtotime($activity['end_time']);
$earlyMinutes = intval($activity['early_minutes'] ?? 0);
$lastMinutes = intval($activity['last_minutes'] ?? 0);

$allowStart = $startTime - ($earlyMinutes * 60);
$allowEnd = $endTime + ($lastMinutes * 60);

if ($now < $allowStart) {
    display_error("該活動尚未開始。<br><BR><small>開始時間：" . date('Y-m-d H:i', $startTime) . "</small>", "info");
}
if ($now > $allowEnd) {
    display_error("該活動已經結束。<br><BR><small>結束時間：" . date('Y-m-d H:i', $endTime) . "</small>", "info");
}
