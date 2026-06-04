<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../inc/smtp-config.php';
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

function fetch_messages($conn, $reportId) {
    $sql = "SELECT * FROM messages WHERE report_id = ? ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reportId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $messages;
}
?>