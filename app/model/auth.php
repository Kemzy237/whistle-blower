<?php
/**
 * Create access token for evidence viewing
 */
function createAccessToken($conn, $reportId, $evidenceId = null, $type = 'view_evidence', $expiresAt = null) {
    $token = bin2hex(random_bytes(32));
    if($expiresAt == null){
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    }
    
    $stmt = $conn->prepare("
        INSERT INTO access_tokens (report_id, token, type, expires_at, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    if ($stmt->execute([$reportId, $token, $type, $expiresAt])) {
        if ($evidenceId) {
            $_SESSION['token_evidence_id'] = $evidenceId;
        }
        return $token;
    }
    return false;
}

function get_access_token($conn, $token) {
    $sql = "SELECT id, report_id, token, type, expires_at, used_at 
    FROM access_tokens 
    WHERE token = ? AND type = 'view_evidence' AND expires_at > NOW() AND used_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$token]);
    $accessToken = $stmt->fetch(PDO::FETCH_ASSOC);
    return $accessToken;
}

function mark_token_as_read($conn, $token) {
    $sql = 'UPDATE access_tokens SET used_at = NOW() WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->execute([$token]);
}

function validate_access_token($conn, $token){
    $sql = "SELECT id, report_id, token, type, expires_at, used_at 
    FROM access_tokens 
    WHERE token = ? AND type = 'view_evidence' AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$token]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}

function check_access_token($conn, $token){
    $sql = "SELECT id, report_id, token, type, expires_at, used_at 
    FROM access_tokens 
    WHERE token = ? AND type = 'view_evidence' AND expires_at > DATE_SUB(NOW(), INTERVAL 1 DAY)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$token]);
    $token = $stmt->fetch(PDO::FETCH_ASSOC);
    return $token;
}

function mark_access_token($conn, $token) {
    $sql = 'UPDATE access_tokens SET used_at = NOW() WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->execute([$token]);
}

function get_user_by_id($conn, $id){
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user;
}

function get_user_notification_settings($conn, $adminId){
    $sql = "SELECT * FROM user_settings WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$adminId]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

function check_if_email_exists($conn, $data){
    $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
    if($stmt->fetch()){
        return true;
    }else{
        return false;
    }
}

function update_user($conn, $data){
    $sql = "UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
    return true;
}

function update_user_password($conn, $data){
    $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
    return true;
}

function insert_update_user_settings($conn, $data){
    $sql = "INSERT INTO user_settings (user_id, notification_enabled, push_notification_token, device_type, updated_at)
    VALUES (?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
        notification_enabled = VALUES(notification_enabled),
        push_notification_token = VALUES(push_notification_token),
        device_type = VALUES(device_type),
        updated_at = NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
    return true;
}

function push_notification_token($conn, $data){
    $sql = "INSERT INTO user_settings (user_id, push_notification_token, device_type, updated_at)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE  
        push_notification_token = VALUES(push_notification_token),
        device_type = VALUES(device_type),
        updated_at = NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
    return true;
}
?>