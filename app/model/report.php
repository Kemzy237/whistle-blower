<?php



use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
// Define encryption key (must match the one in submit-report.php

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
    
    if ($categoryFilter && $categoryFilter !== 'all') {
        $sql .= " AND c.name = :category";
        $params[':category'] = $categoryFilter;
    }
    
    $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $conn->prepare($sql);
    
    foreach ($params as $placeholder => $value) {
        $stmt->bindValue($placeholder, $value, PDO::PARAM_STR);
    }
    
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

/**
 * Send email notification to admin about new report using PHPMailer
 */
function sendNewReportEmail($adminEmail, $adminName, $reportId, $trackingCode, $category, $priority) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = SMTP_AUTH;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->setLanguage('en');
        
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($adminEmail, $adminName);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        $mail->isHTML(true);
        $mail->Subject = "🔔 New Whistleblower Report Submitted - Report #" . $reportId;
        
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

function delete_report($conn, $reportId) {
    $sql = "DELETE FROM reports WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reportId]);
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

function get_public_approved_report_by_id($conn, $id){
    $sql = "SELECT e.*, r.tracking_code 
    FROM evidences e 
    JOIN reports r ON e.report_id = r.id 
    WHERE e.report_id = ? AND e.is_public = TRUE
    AND r.visibility = 'public' AND r.publication_status = 'approved'
    ORDER BY e.id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}

function insert_report($conn, $data){
    $sql = "INSERT INTO reports (
        anonymous_session_id, tracking_code, category_id, encrypted_description,
        status, priority, view_once_enabled, expires_at, created_at, updated_at
    ) VALUES (?, ?, ?, ?, 'new', ?, FALSE, ?, NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
}

function get_approved_reports_details($conn, $id){
    $sql = "SELECT r.id, r.tracking_code, r.category_id, r.encrypted_description, 
           r.status, r.priority, r.created_at, r.updated_at,
           c.name as category_name
    FROM reports r
    JOIN categories c ON r.category_id = c.id
    WHERE r.id = ? AND r.visibility = 'public' 
    AND r.publication_status = 'approved'
    AND (r.expires_at IS NULL OR r.expires_at > NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result;
}
?>