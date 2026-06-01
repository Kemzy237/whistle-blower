<?php
// admin-logout.php
session_start();

// Log the logout action
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    require_once 'db_connection.php';
    
    $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    
    $stmt = $conn->prepare("
        INSERT INTO audit_logs (admin_user_id, action, ip_hash, user_agent_hash, created_at)
        VALUES (?, 'admin_logout', ?, ?, NOW())
    ");
    $stmt->execute([$_SESSION['admin_id'] ?? null, $ipHash, $userAgentHash]);
}

// Destroy all session data
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirect to login page
header('Location: admin-login.php');
exit;
?>