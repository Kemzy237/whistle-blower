<?php
// admin-logout.php
session_start();
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: admin-login.php");
    exit();
}

// Log the logout action
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    require_once 'db_connection.php';
    require_once __DIR__.'/app/model/index.php';
    
    $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

    $categoryLogData = [
        'admin_user_id'   => array($_SESSION['admin_id'] ?? null),
        'action'          => 'admin_logout',
        'ip_hash'         => $ipHash,
        'user_agent_hash' => $userAgentHash,
        'created_at'      => date('Y-m-d H:i:s') // Replacing NOW() with a PHP timestamp
    ];
    log_system_action($conn, 'audit_logs', $categoryLogData);
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