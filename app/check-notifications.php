<?php
// app/check-notifications.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../db_connection.php';
require_once '../app/model/report.php';

$unreadCount = getUnreadMessagesCount($conn);

header('Content-Type: application/json');
echo json_encode(['unread_count' => $unreadCount]);