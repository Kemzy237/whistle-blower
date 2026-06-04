<?php
// app/check-notifications.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: admin-login.php");
    exit();
}

require_once '../db_connection.php';
require_once '../app/model/index.php';

$unreadCount = getUnreadMessagesCount($conn);

header('Content-Type: application/json');
echo json_encode(['unread_count' => $unreadCount]);