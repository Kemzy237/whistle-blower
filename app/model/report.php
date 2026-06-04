<?php
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: ../../admin-login.php");
    exit();
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../inc/smtp-config.php';
// include "../../inc/smtp-config.php";

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

/**
 * Get count of reports with unread messages from whistleblowers
 */
function getUnreadMessagesCount($conn) {
    $sql = "
        SELECT COUNT(DISTINCT r.id) as count
        FROM reports r
        JOIN messages m ON r.id = m.report_id
        WHERE m.sender_type = 'whistleblower' 
        AND m.is_read = FALSE
        AND r.status != 'closed'
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['count'] : 0;
}

/**
 * Get unread messages count for a specific report
 */
function getReportUnreadCount($conn, $reportId) {
    $sql = "
        SELECT COUNT(*) as count
        FROM messages
        WHERE report_id = ? 
        AND sender_type = 'whistleblower' 
        AND is_read = FALSE
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reportId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['count'] : 0;
}

/**
 * Mark all messages in a report as read (for when admin views the report)
 */
function markReportMessagesAsRead($conn, $reportId) {
    $stmt = $conn->prepare("
        UPDATE messages 
        SET is_read = TRUE, read_at = NOW() 
        WHERE report_id = ? AND sender_type = 'whistleblower' AND is_read = FALSE
    ");
    return $stmt->execute([$reportId]);
}

// app/model/report.php - Add these functions



/**
 * Send email notification to admin about new report using PHPMailer
 */
function sendNewReportEmail($adminEmail, $adminName, $reportId, $trackingCode, $category, $priority) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->setLanguage('en');
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($adminEmail, $adminName);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Email content
        $mail->isHTML(true);
        $mail->Subject = "🔔 New Whistleblower Report Submitted - Report #" . $reportId;
        
        // HTML Email Template
        $htmlContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>New Report Notification</title>
            <style>
                body {
                    font-family: "Inter", Arial, sans-serif;
                    background: #f4f4f4;
                    margin: 0;
                    padding: 20px;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: #ffffff;
                    border-radius: 16px;
                    overflow: hidden;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #4f46e5, #7c3aed);
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    color: white;
                    margin: 0;
                    font-size: 24px;
                }
                .content {
                    padding: 30px;
                    background: #ffffff;
                }
                .report-details {
                    background: #f8fafc;
                    border-radius: 12px;
                    padding: 20px;
                    margin: 20px 0;
                    border-left: 4px solid #4f46e5;
                }
                .detail-row {
                    margin: 10px 0;
                    padding: 8px 0;
                    border-bottom: 1px solid #e2e8f0;
                }
                .detail-label {
                    font-weight: 600;
                    color: #1e293b;
                    display: inline-block;
                    width: 120px;
                }
                .detail-value {
                    color: #475569;
                }
                .tracking-code {
                    background: #1e293b;
                    color: white;
                    padding: 8px 16px;
                    border-radius: 8px;
                    font-family: monospace;
                    font-size: 18px;
                    letter-spacing: 1px;
                    display: inline-block;
                    margin: 10px 0;
                }
                .button {
                    display: inline-block;
                    background: linear-gradient(135deg, #4f46e5, #7c3aed);
                    color: white;
                    text-decoration: none;
                    padding: 12px 24px;
                    border-radius: 8px;
                    margin-top: 20px;
                    font-weight: 600;
                }
                .footer {
                    background: #f1f5f9;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #64748b;
                }
                .priority-high { color: #ef4444; font-weight: bold; }
                .priority-medium { color: #f59e0b; font-weight: bold; }
                .priority-low { color: #10b981; font-weight: bold; }
                .priority-critical { color: #dc2626; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔔 New Whistleblower Report</h1>
                </div>
                <div class="content">
                    <p>Dear <strong>' . htmlspecialchars($adminName) . '</strong>,</p>
                    <p>A new anonymous report has been submitted on the WhistleGuard platform.</p>
                    
                    <div class="report-details">
                        <div class="detail-row">
                            <span class="detail-label">Report ID:</span>
                            <span class="detail-value">#' . $reportId . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tracking Code:</span>
                            <span class="detail-value"><code>' . htmlspecialchars($trackingCode) . '</code></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Category:</span>
                            <span class="detail-value">' . htmlspecialchars($category) . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Priority:</span>
                            <span class="detail-value priority-' . strtolower($priority) . '">' . ucfirst($priority) . '</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Submitted:</span>
                            <span class="detail-value">' . date('F j, Y g:i A') . '</span>
                        </div>
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="http://' . $_SERVER['HTTP_HOST'] . '/whistleblower/admin-view-report.php?id=' . $reportId . '" class="button">
                            📋 View Report Details
                        </a>
                    </div>
                </div>
                <div class="footer">
                    <p>This is an automated notification from WhistleGuard.<br>
                    Please do not reply to this email.</p>
                    <p>&copy; ' . date('Y') . ' WhistleGuard</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->Body = $htmlContent;
        $mail->AltBody = "New Report #{$reportId}\nTracking: {$trackingCode}\nCategory: {$category}\nPriority: {$priority}\n\nView at: http://" . $_SERVER['HTTP_HOST'] . "/whistleblower/admin-view-report.php?id={$reportId}";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send email notification for new message
 */
function sendNewMessageEmail($adminEmail, $adminName, $reportId, $trackingCode, $messagePreview) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($adminEmail, $adminName);
        
        $mail->isHTML(true);
        $mail->Subject = "💬 New Message Received - Report #" . $reportId;
        
        $htmlContent = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>New Message Notification</title>
            <style>
                body {
                    font-family: "Inter", Arial, sans-serif;
                    background: #f4f4f4;
                    margin: 0;
                    padding: 20px;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: #ffffff;
                    border-radius: 16px;
                    overflow: hidden;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #10b981, #059669);
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    color: white;
                    margin: 0;
                    font-size: 24px;
                }
                .content {
                    padding: 30px;
                    background: #ffffff;
                }
                .message-preview {
                    background: #f0fdf4;
                    border-left: 4px solid #10b981;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 8px;
                }
                .button {
                    display: inline-block;
                    background: linear-gradient(135deg, #4f46e5, #7c3aed);
                    color: white;
                    text-decoration: none;
                    padding: 12px 24px;
                    border-radius: 8px;
                    margin-top: 20px;
                    font-weight: 600;
                }
                .footer {
                    background: #f1f5f9;
                    padding: 20px;
                    text-align: center;
                    font-size: 12px;
                    color: #64748b;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>💬 New Message Received</h1>
                </div>
                <div class="content">
                    <p>Dear <strong>' . htmlspecialchars($adminName) . '</strong>,</p>
                    <p>The whistleblower has sent a new message regarding Report #' . $reportId . '.</p>
                    
                    <div class="message-preview">
                        <strong>Message Preview:</strong><br>
                        "' . htmlspecialchars(substr($messagePreview, 0, 200)) . (strlen($messagePreview) > 200 ? '...' : '') . '"
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="http://' . $_SERVER['HTTP_HOST'] . '/whistleblower/admin-view-report.php?id=' . $reportId . '" class="button">
                            💬 Reply to Message
                        </a>
                    </div>
                </div>
                <div class="footer">
                    <p>This is an automated notification from WhistleGuard.</p>
                    <p>&copy; ' . date('Y') . ' WhistleGuard</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->Body = $htmlContent;
        $mail->AltBody = "New message on Report #{$reportId}\n\nMessage: {$messagePreview}\n\nReply at: http://" . $_SERVER['HTTP_HOST'] . "/whistleblower/admin-view-report.php?id={$reportId}";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}

function update_report($conn, $data){
    $sql = "UPDATE reports SET category_id = ?, 
            encrypted_description = ?, 
            priority = ?, 
            visibility = ?, 
            publication_status = ?,
            expires_at = ?,
            updated_at = NOW()
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
}

 function get_file_path($conn, $data){
    $sql = "SELECT encrypted_file_path FROM evidences WHERE id = ? AND report_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
    $evidence = $stmt->fetch(PDO::FETCH_ASSOC);
    return $evidence;
}

 function delete_evidence($conn, $data){
    $sql = "DELETE FROM evidences WHERE id = ? AND report_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
}

 function delete_report($conn, $reportId) {
    $sql = "DELETE FROM reports WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reportId]);
}

// function log_admin_action($conn, $action) {
//     $sql = 'INSERT INTO audit_logs (admin_user_id, action, ip_hash, user_agent_hash, created_at)
//     VALUES (?, ?, ?, ?, NOW())';
//     $stmt = $conn->prepare($sql);
//     $stmt->execute($action);
// }

function log_system_action($conn, $table, $data) {
    $columns = array_keys($data);
    $columnString = '`' . implode('`, `', $columns) . '`';
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $sql = "INSERT INTO `{$table}` ({$columnString}) VALUES ({$placeholders})";
    try {
        $stmt = $conn->prepare($sql);
        return $stmt->execute(array_values($data));
    } catch (PDOException $e) {
        error_log("Dynamic logging failed: " . $e->getMessage());
        return false;
    }
}

 function insert_category($conn, $data) {
    $sql = 'INSERT INTO categories (name, description, is_active, created_at) VALUES (?, ?, 1, NOW())';
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
}

function update_category($conn, $data) {
    $sql = 'UPDATE categories SET name = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
}

function count_category_reports($conn, $categoryId) {
    $sql = 'SELECT COUNT(*) as count FROM reports WHERE category_id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->execute([$categoryId]);
    $result = $stmt->fetchAll();
    return $result ? $result['total'] : 0;
}
function delete_category($conn, $categoryId) {
    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$categoryId]);
}

function get_ordered_categories($conn) {
    $sql = "SELECT * FROM categories ORDER BY id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}

function get_system_stats($conn){
    $sql = "SELECT 
        COUNT(*) as total_reports,
        COUNT(DISTINCT anonymous_session_id) as unique_whistleblowers,
        (SELECT COUNT(*) FROM messages) as total_messages,
        (SELECT COUNT(*) FROM evidences) as total_evidences,
        (SELECT COUNT(*) FROM users WHERE role IN ('admin', 'super_admin')) as total_admins
    FROM reports";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $systemStats = $stmt->fetch(PDO::FETCH_ASSOC);
    return $systemStats;
}

function get_database_size($conn){
    $sql = "SELECT 
        table_schema as 'database',
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
    FROM information_schema.tables 
    WHERE table_schema = 'whistleblower'
    GROUP BY table_schema";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $size = $stmt->fetch(PDO::FETCH_ASSOC);
    return $size;
}

function count_unread_reports($conn){
    $sql = "SELECT COUNT(DISTINCT r.id) as count
    FROM reports r
    JOIN messages m ON r.id = m.report_id
    WHERE m.sender_type = 'whistleblower' 
    AND m.is_read = FALSE
    AND r.status != 'closed";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result["count"];
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

// Function to send notification
function sendNotification($userId, $title, $message, $type = 'general') {
    global $conn;
    
    // Get user's push token and settings
    $stmt = $conn->prepare("
        SELECT us.push_notification_token, us.device_type, us.notification_enabled, u.email 
        FROM user_settings us
        JOIN users u ON us.user_id = u.id
        WHERE us.user_id = ?
    ");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData || !$userData['notification_enabled']) {
        return false;
    }
    
    $pushToken = $userData['push_notification_token'];
    $deviceType = $userData['device_type'];
    $email = $userData['email'];
    
    // Send push notification via OneSignal
    if (!empty($pushToken)) {
        sendOneSignalNotification($pushToken, $title, $message, $deviceType);
    }
    
    // Send email notification (optional)
    if ($type === 'new_message') {
        sendEmailNotification($email, $title, $message);
    }
    
    return true;
}

// Function to send OneSignal push notification
function sendOneSignalNotification($pushToken, $title, $message, $deviceType = 'web') {
    // Replace with your OneSignal App ID and API Key
    $appId = 'YOUR_ONESIGNAL_APP_ID';
    $apiKey = 'YOUR_ONESIGNAL_API_KEY';
    
    $content = array(
        "en" => $message
    );
    
    $fields = array(
        'app_id' => $appId,
        'include_player_ids' => array($pushToken),
        'data' => array("foo" => "bar"),
        'contents' => $content,
        'headings' => array("en" => $title),
        'priority' => 10
    );
    
    $fields = json_encode($fields);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Basic ' . $apiKey
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

// Function to send email notification
function sendEmailNotification($to, $subject, $message) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: noreply@whistleguard.com' . "\r\n";
    
    $htmlMessage = "
    <html>
    <head>
        <title>{$subject}</title>
        <style>
            body { font-family: Arial, sans-serif; background: #0a0e1a; color: #fff; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 20px; text-align: center; border-radius: 10px; }
            .content { background: rgba(20, 24, 36, 0.95); padding: 20px; border-radius: 10px; margin-top: 20px; }
            .button { background: #4f46e5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>WhistleGuard Notification</h2>
            </div>
            <div class='content'>
                <h3>{$subject}</h3>
                <p>{$message}</p>
                <a href='https://yourdomain.com/admin-dashboard.php' class='button'>View Dashboard</a>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return mail($to, $subject, $htmlMessage, $headers);
}

// Function to send test notification
function sendTestNotification($userId, $pushToken, $deviceType) {
    sendOneSignalNotification($pushToken, 'Test Notification', 'Your notifications are working! Welcome to WhistleGuard.', $deviceType);
}


function count_user_activity($conn, $userId) {
    $sql = 'SELECT COUNT(*) as total_actions FROM audit_logs WHERE admin_user_id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
    $totalActions = $stmt->fetch(PDO::FETCH_ASSOC)['total_actions'];
    return $totalActions;
}

function get_recent_activity($conn, $adminId){
    $sql = "SELECT action, COUNT(*) as count 
    FROM audit_logs 
    WHERE admin_user_id = ? 
    GROUP BY action 
    ORDER BY count DESC 
    LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$adminId]);
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $recentActivities;
}

function update_report_status($conn, $data){
    $sql = "UPDATE reports SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
}

function update_report_visibility($conn, $data){
    $sql = "UPDATE reports SET visibility = ?, publication_status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
}

 function update_evidence_visibility($conn, $data){
    $sql = "UPDATE evidences SET is_public = ? WHERE id = ? AND report_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
}

 function get_evidence_by_id($conn, $evidenceId) {
    $sql = "SELECT e.*, r.tracking_code 
    FROM evidences e 
    JOIN reports r ON e.report_id = r.id 
    WHERE e.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$evidenceId]);
    $evidence = $stmt->fetch(PDO::FETCH_ASSOC);
    return $evidence;
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

function get_latest_report_evidence($conn, $accessToken){
    $sql = "SELECT e.*, r.tracking_code 
    FROM evidences e 
    JOIN reports r ON e.report_id = r.id 
    WHERE e.report_id = ?
    ORDER BY e.id DESC 
    LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$accessToken]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}


function update_view_count($conn, $id){
    $sql = "UPDATE evidences 
    SET view_count = view_count + 1, updated_at = NOW() 
    WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
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

function get_public_approved_evidence_by_id($conn, $evidenceId) {
    $sql = "SELECT e.*, r.tracking_code 
    FROM evidences e 
    JOIN reports r ON e.report_id = r.id 
    WHERE e.id = ? AND e.is_public = TRUE
    AND r.visibility = 'public' AND r.publication_status = 'approved'";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$evidenceId]);
    $approved = $stmt->fetch(PDO::FETCH_ASSOC);
    return $approved;
}

 function get_public_approved_report_by_id($conn, $evidenceId){
    $sql = "SELECT e.*, r.tracking_code 
    FROM evidences e 
    JOIN reports r ON e.report_id = r.id 
    WHERE e.report_id = ? AND e.is_public = TRUE
    AND r.visibility = 'public' AND r.publication_status = 'approved'
    ORDER BY e.id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$evidenceId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}

