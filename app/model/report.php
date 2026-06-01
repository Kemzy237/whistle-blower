<?php
// app/model/report.php

// Define encryption key (must match the one in submit-report.php)
define('ENCRYPTION_KEY', hex2bin('7a8f5c3e2d1b4a6c9e7f8d3c2b1a4f6e8d7c9a5b3e2f1c8d7a6b4f2e1c3d5a7b'));

/**
 * Decrypt data using AES-256-CBC
 */
function decryptData($encryptedData, $key) {
    $data = base64_decode($encryptedData);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

/**
 * Encrypt data (for messages)
 */
function encryptData($data, $key) {
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted);
}

/**
 * Get report by tracking code
 */
function getReportByTrackingCode($conn, $trackingCode) {
    $stmt = $conn->prepare("
        SELECT id, tracking_code, category_id, encrypted_description, status, priority, 
               view_once_enabled, expires_at, created_at, updated_at, visibility, publication_status
        FROM reports 
        WHERE tracking_code = ? AND (expires_at IS NULL OR expires_at > NOW())
    ");
    $stmt->execute([$trackingCode]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get category name by ID
 */
function getCategoryName($conn, $categoryId) {
    $stmt = $conn->prepare("SELECT name, description FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['name'] : 'Unknown';
}

/**
 * Get messages for a report
 */
function getMessages($conn, $reportId, $limit = 50) {
    $cleanLimit = (int)$limit;

    $stmt = $conn->prepare("
        SELECT id, sender_type, encrypted_message, is_read, is_delivered, 
               created_at, read_at, delivered_at
        FROM messages 
        WHERE report_id = ? AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY created_at ASC 
        LIMIT $cleanLimit
    ");
    
    $stmt->execute([$reportId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get evidences for a report
 */
function getEvidences($conn, $reportId, $publicOnly = false) {
    $sql = "
        SELECT id, encrypted_name, mime_type, size_bytes, view_count, max_views, 
               expires_at, created_at, is_public
        FROM evidences 
        WHERE report_id = ? AND (expires_at IS NULL OR expires_at > NOW())
        AND (max_views IS NULL OR view_count < max_views)
    ";
    
    if ($publicOnly) {
        $sql .= " AND is_public = TRUE";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reportId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get all public reports for dashboard
 */
function getPublicReports($conn, $limit = 50, $offset = 0, $categoryFilter = null) {
    $sql = "
        SELECT r.id, r.tracking_code, r.category_id, r.encrypted_description, 
               r.status, r.priority, r.created_at, r.updated_at,
               c.name as category_name
        FROM reports r
        JOIN categories c ON r.category_id = c.id
        WHERE r.visibility = 'public' 
        AND r.publication_status = 'approved'
        AND (r.expires_at IS NULL OR r.expires_at > NOW())
        AND r.status != 'closed'
    ";
    
    $params = [];
    
    // 1. Build conditional filter query strings
    if ($categoryFilter && $categoryFilter !== 'all') {
        $sql .= " AND c.name = :category";
        $params[':category'] = $categoryFilter;
    }
    
    // 2. Append named placeholders specifically for pagination
    $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    
    // 3. Bind the optional search parameters natively as strings
    foreach ($params as $placeholder => $value) {
        $stmt->bindValue($placeholder, $value, PDO::PARAM_STR);
    }
    
    // 4. Force strict integer binding for pagination parameters
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Count public reports
 */
function countPublicReports($conn, $categoryFilter = null) {
    $sql = "
        SELECT COUNT(*) as total
        FROM reports r
        JOIN categories c ON r.category_id = c.id
        WHERE r.visibility = 'public' 
        AND r.publication_status = 'approved'
        AND (r.expires_at IS NULL OR r.expires_at > NOW())
        AND r.status != 'closed'
    ";
    
    $params = [];
    
    if ($categoryFilter && $categoryFilter !== 'all') {
        $sql .= " AND c.name = ?";
        $params[] = $categoryFilter;
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['total'] : 0;
}

/**
 * Get all categories for filter
 */
function getAllCategories($conn) {
    $stmt = $conn->prepare("SELECT id, name, description FROM categories WHERE is_active = TRUE ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Send a new message
 */
function sendMessage($conn, $reportId, $message, $senderType = 'whistleblower') {
    $encryptedMessage = encryptData($message, ENCRYPTION_KEY);
    
    $stmt = $conn->prepare("
        INSERT INTO messages (report_id, sender_type, encrypted_message, is_read, is_delivered, created_at)
        VALUES (?, ?, ?, FALSE, TRUE, NOW())
    ");
    
    return $stmt->execute([$reportId, $senderType, $encryptedMessage]);
}

/**
 * Mark messages as read
 */
function markMessagesAsRead($conn, $reportId, $senderType = 'admin') {
    $stmt = $conn->prepare("
        UPDATE messages 
        SET is_read = TRUE, read_at = NOW() 
        WHERE report_id = ? AND sender_type = ? AND is_read = FALSE
    ");
    return $stmt->execute([$reportId, $senderType]);
}

/**
 * Create access token for evidence viewing
 */
function createAccessToken($conn, $reportId, $evidenceId = null, $type = 'view_evidence') {
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
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

/**
 * Generate secure tracking code
 */
function generateTrackingCode() {
    return strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
}

/**
 * Generate anonymous session ID
 */
function getAnonymousSessionId() {
    if (!isset($_SESSION['anonymous_session_id'])) {
        $_SESSION['anonymous_session_id'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['anonymous_session_id'];
}

/**
 * Get category ID by name
 */
function getCategoryId($conn, $categoryName) {
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$categoryName]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['id'] : 6;
}

/**
 * Sanitize and validate file upload
 */
function handleFileUpload($file, $conn, $reportId) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("File upload error: " . $file['error']);
        return null;
    }
    
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        error_log("File too large: " . $file['size']);
        return null;
    }
    
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimeTypes)) {
        error_log("Invalid file type: " . $mimeType);
        return null;
    }
    
    $fileContent = file_get_contents($file['tmp_name']);
    $encryptedContent = encryptData($fileContent, ENCRYPTION_KEY);
    $encryptedFilename = encryptData($file['name'], ENCRYPTION_KEY);
    
    $uploadDir = __DIR__ . '/../assets/evidences/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0700, true);
    }
    
    $fileHash = hash('sha256', $file['name'] . random_bytes(32));
    $filePath = $uploadDir . $fileHash . '.enc';
    file_put_contents($filePath, $encryptedContent);
    
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $stmt = $conn->prepare("
        INSERT INTO evidences (report_id, encrypted_file_path, encrypted_name, mime_type, size_bytes, expires_at, is_public)
        VALUES (?, ?, ?, ?, ?, ?, FALSE)
    ");
    
    $encryptedPath = encryptData($filePath, ENCRYPTION_KEY);
    $stmt->execute([$reportId, $encryptedPath, $encryptedFilename, $mimeType, $file['size'], $expiresAt]);
    
    return $conn->lastInsertId();
}

function count_reports($conn, $status){
    $sql = "SELECT COUNT(*) as total FROM reports WHERE status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$status]);
    $reports = $stmt->fetch(PDO::FETCH_ASSOC);
    return $reports ? $reports["total"] :0;
}

function count_all_reports($conn){
    $sql = "SELECT COUNT(*) as total FROM reports";
    $stmt = $conn->prepare($sql);
    $stmt->execute([]);
    $reports = $stmt->fetch(PDO::FETCH_ASSOC);
    return $reports ? $reports["total"] :0;
}

function get_priority_distribution($conn) {
    $sql = "SELECT priority, COUNT(*) as count FROM reports GROUP BY priority";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

function get_monthly_report_trends($conn){
    $sql = "SELECT DATE_FORMAT(created_at, '%M') as month, 
            COUNT(*) as count FROM reports 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY YEAR(created_at), MONTH(created_at)
            ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $monthlyTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $monthlyTrends;
}

function get_category_distribution($conn){
    $sql = "SELECT c.name, COUNT(r.id) as count
            FROM categories c
            LEFT JOIN reports r ON c.id = r.category_id
            GROUP BY c.id
            ORDER BY count DESC
            LIMIT 6";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

function recent_reports($conn){
    $sql = "SELECT r.id, r.tracking_code, r.status, r.priority, r.created_at, c.name as category_name
            FROM reports r
            JOIN categories c ON r.category_id = c.id
            ORDER BY r.created_at DESC
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $reports;
}

function get_weekly_activity($conn) {
    $sql = "SELECT 
            DAYNAME(created_at) as day,
            COUNT(*) as count
            FROM reports
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DAYOFWEEK(created_at)
            ORDER BY DAYOFWEEK(created_at)";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

function categories_filters($conn){
    $sql = "SELECT id, name FROM categories WHERE is_active = TRUE ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $categories;
}

function complete_report_category_data($conn, $id){
    $sql = "SELECT r.*, c.name as category_name, c.description as category_description
            FROM reports r
            JOIN categories c ON r.category_id = c.id
            WHERE r.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    return $report;
}

function fetch_messages($conn, $reportId) {
    $sql = "SELECT * FROM messages WHERE report_id = ? ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reportId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $messages;
}

function fetch_evidences($conn, $reportId){
    $sql = "SELECT * FROM evidences WHERE report_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reportId]);
    $evidences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $evidences;
}

function get_report_without_catDescription($conn, $reportid){
    $sql = "SELECT r.*, c.name as category_name, c.id as category_id 
            FROM reports r
            JOIN categories c ON r.category_id = c.id
            WHERE r.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reportid]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    return $report;
}

function fetch_report_evidence($conn, $reportId){
    $sql = "SELECT * FROM evidences WHERE report_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reportId]);
    $evidences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $evidences;
}