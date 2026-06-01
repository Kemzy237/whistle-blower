<?php
// app/view-reports.php
// Handle report tracking and secure message communication (No AJAX)

// Start session for anonymous session ID if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include "../db_connection.php";
include "model/report.php";


// Handle different POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Handle tracking form submission
    if (isset($_POST['tracking_code']) && !isset($_POST['action'])) {
        $trackingCode = trim($_POST['tracking_code']);
        
        if (empty($trackingCode)) {
            $_SESSION['track_error'] = 'Please enter a tracking code';
            header('Location: ..index.php');
            exit;
        }
        
        $report = getReportByTrackingCode($conn, $trackingCode);
        
        if (!$report) {
            $_SESSION['track_error'] = 'Invalid or expired tracking code. Please check and try again.';
            header('Location: ../index.php');
            exit;
        }
        
        // Store report data in session for the tracking page
        $_SESSION['tracked_report'] = $report;
        $_SESSION['tracked_report_id'] = $report['id'];
        $_SESSION['tracking_code'] = $trackingCode;
        
        // Create audit log
        $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        
        $stmt = $conn->prepare("
            INSERT INTO audit_logs (report_id, action, ip_hash, user_agent_hash, metadata, created_at)
            VALUES (?, 'report_viewed', ?, ?, ?, NOW())
        ");
        $metadata = json_encode(['tracking_code' => $trackingCode]);
        $stmt->execute([$report['id'], $ipHash, $userAgentHash, $metadata]);
        
        header('Location: ../view-reports.php');
        exit;
    }
    
    // Handle sending a new message
    if (isset($_POST['action']) && $_POST['action'] === 'send_message') {
        $trackingCode = $_POST['tracking_code'] ?? '';
        $message = trim($_POST['message'] ?? '');
        
        if (empty($trackingCode) || empty($message)) {
            $_SESSION['message_error'] = 'Message cannot be empty';
            header('Location: ../view-reports.php');
            exit;
        }
        
        $report = getReportByTrackingCode($conn, $trackingCode);
        
        if (!$report) {
            $_SESSION['message_error'] = 'Invalid tracking code';
            header('Location: ../view-reports.php');
            exit;
        }
        
        if (sendMessage($conn, $report['id'], $message, 'whistleblower')) {
            $_SESSION['message_success'] = 'Message sent successfully';
        } else {
            $_SESSION['message_error'] = 'Failed to send message';
        }
        
        header('Location: ../view-reports.php');
        exit;
    }
    
    // Handle viewing evidence - create access token
    if (isset($_POST['action']) && $_POST['action'] === 'view_evidence') {
        $trackingCode = $_POST['tracking_code'] ?? '';
        $evidenceId = $_POST['evidence_id'] ?? '';
        
        if (empty($trackingCode) || empty($evidenceId)) {
            $_SESSION['evidence_error'] = 'Invalid request';
            header('Location: ../view-reports.php');
            exit;
        }
        
        $report = getReportByTrackingCode($conn, $trackingCode);
        
        if (!$report) {
            $_SESSION['evidence_error'] = 'Invalid tracking code';
            header("Location: ../view-reports.php&error='invalid tracking code'");
            exit;
        }
        
        $token = createAccessToken($conn, $report['id'], $evidenceId);
        
        if ($token) {
            $_SESSION['access_token'] = $token;
            $_SESSION['evidence_id_to_view'] = $evidenceId;
            header('Location: ../view-evidence.php');
            exit;
        } else {
            $_SESSION['evidence_error'] = 'Failed to create access token';
            header('Location: ../view-reports.php');
            exit;
        }
    }
}

// Handle logout/clear session


// Redirect if not properly accessed
// header('Location: /../view-reports.php&id="directory falilu"');
// exit;