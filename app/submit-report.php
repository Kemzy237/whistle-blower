<?php
// app/submit-report.php
// Handle anonymous report submission with encryption and secure file upload

// Start session for anonymous session ID if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include "../db_connection.php";
include "model/report.php";

// Define encryption key (store this in environment variables in production!)
// This is a sample key - in production, use a secure key management system



// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

try {
    // Validate required fields
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = trim($_POST['priority'] ?? 'medium');
    $selfDestruct = isset($_POST['self_destruct']) ? filter_var($_POST['self_destruct'], FILTER_VALIDATE_BOOLEAN) : false;
    
    if (empty($category)) {
        throw new Exception('Category is required');
    }
    
    if (empty($description)) {
        throw new Exception('Description is required');
    }
    
    // Validate priority
    $validPriorities = ['low', 'medium', 'high', 'critical'];
    $priority = strtolower($priority);
    if (!in_array($priority, $validPriorities)) {
        $priority = 'medium';
    }
    
    // Get anonymous session ID
    $anonymousSessionId = getAnonymousSessionId();
    
    // Get IP hash for audit (but not stored with report)
    $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $userAgentHash = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
    
    // Get category ID
    $categoryId = getCategoryId($conn, $category);
    
    // Encrypt description
    $encryptedDescription = encryptData($description, ENCRYPTION_KEY);
    
    // Generate tracking code
    $trackingCode = generateTrackingCode();
    
    // Calculate expiration date if self-destruct enabled
    $expiresAt = null;
    if ($selfDestruct) {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    }
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Insert report
    $stmt = $conn->prepare("
        INSERT INTO reports (
            anonymous_session_id, tracking_code, category_id, encrypted_description,
            status, priority, view_once_enabled, expires_at, created_at, updated_at
        ) VALUES (?, ?, ?, ?, 'new', ?, FALSE, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $anonymousSessionId,
        $trackingCode,
        $categoryId,
        $encryptedDescription,
        $priority,
        $expiresAt
    ]);
    
    $reportId = $conn->lastInsertId();
    
    // Handle file upload if present
    $evidenceId = null;
    if (isset($_FILES['evidence']) && $_FILES['evidence']['error'] !== UPLOAD_ERR_NO_FILE) {
        $evidenceId = handleFileUpload($_FILES['evidence'], $conn, $reportId);
    }
    
    // Create audit log entry (IP hashed for privacy but can detect patterns)
    $stmt = $conn->prepare("
        INSERT INTO audit_logs (report_id, action, ip_hash, user_agent_hash, metadata, created_at)
        VALUES (?, 'report_submitted', ?, ?, ?, NOW())
    ");
    
    $metadata = json_encode([
        'category' => $category,
        'priority' => $priority,
        'has_evidence' => $evidenceId !== null,
        'self_destruct' => $selfDestruct
    ]);
    
    $stmt->execute([$reportId, $ipHash, $userAgentHash, $metadata]);
    
    // Update anonymous session report count
    $stmt = $conn->prepare("
        INSERT INTO anonymous_sessions (session_id, ip_hash, user_agent_hash, report_count, last_activity)
        VALUES (?, ?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE
            report_count = report_count + 1,
            last_activity = NOW(),
            ip_hash = VALUES(ip_hash),
            user_agent_hash = VALUES(user_agent_hash)
    ");
    
    $stmt->execute([$anonymousSessionId, $ipHash, $userAgentHash]);
    
    // Commit transaction
    $conn->commit();
    
    // Store tracking code in session for display on success page
    $_SESSION['last_tracking_code'] = $trackingCode;
    $_SESSION['last_report_success'] = true;

    $stmt = $conn->prepare("SELECT email, name FROM users WHERE role IN ('admin', 'super_admin') AND is_active = 1");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category name for email
$categoryName = getCategoryName($conn, $categoryId);

// Send email to all admins
foreach ($admins as $admin) {
    sendNewReportEmail(
        $admin['email'],
        $admin['name'],
        $reportId,
        $trackingCode,
        $categoryName,
        $priority
    );
}
    
    // Redirect to success page
    header('Location: ../report-success.php');
    exit;
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Database error in submit-report.php: " . $e->getMessage());
    
    $_SESSION['last_report_error'] = "A database error occurred. Please try again later.";
    header('Location: ../report-error.php');
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Error in submit-report.php: " . $e->getMessage());
    
    $_SESSION['last_report_error'] = $e->getMessage();
    header('Location: ../report-error.php');
    exit;
}