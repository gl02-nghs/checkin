<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'do_checkin') {
        $sigData = $_POST['signature_data'] ?? '';
        $saveToProfile = ($_POST['save_to_profile'] ?? '0') === '1';

        if (!empty($sigData)) {
            // 寫入 attendance 表
            $ins = $db->prepare("INSERT INTO attendance (activity_id, identifier, signature_path, checkin_time) VALUES (?, ?, ?, NOW())");
            $ins->execute([$activityId, $user['email'], $sigData]);

            // 如果同意保存簽名
            if ($saveToProfile) {
                $upd = $db->prepare("UPDATE users SET signature_base64 = ? WHERE id = ?");
                $upd->execute([$sigData, $user['id']]);
            }
            display_success("簽到成功！");
        }
    }

    if ($action === 'clear_auth') {
        setcookie('remember_token', '', time() - 3600, "/");
        header("Location: signin.php?id=" . $activityId);
        exit;
    }
}
