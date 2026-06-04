<?php
// app/create-public-access-token.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/model/index.php';

$reportId = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
$evidenceId = isset($_POST['evidence_id']) ? (int)$_POST['evidence_id'] : 0;
$unlimited = isset($_POST['unlimited']) ? true : false;

if (!$reportId || !$evidenceId) {
    echo json_encode(['success' => false, 'error' => 'Report ID and Evidence ID required']);
    exit;
}

// Verify report is public and approved
$report = get_public_approved_report_by_id($conn, $reportId);

if (!$report) {
    echo json_encode(['success' => false, 'error' => 'Report not found or not public']);
    exit;
}

// Verify evidence exists and is public (remove max_views restriction for unlimited access)
$data = array($evidenceId, $reportId);
$evidence = get_public_evidence_by_id($conn, $data, $unlimited);

if (!$evidence) {
    echo json_encode(['success' => false, 'error' => 'Evidence not found or not public']);
    exit;
}

// Create access token with appropriate expiry
if ($unlimited) {
    // Create a token that doesn't expire (or expires far in future)
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 years')); // 10 years expiry for unlimited access
    
    $stmt = $conn->prepare("
        INSERT INTO access_tokens (report_id, token, type, expires_at, created_at)
        VALUES (?, ?, 'view_evidence', ?, NOW())
    ");
    
    if ($stmt->execute([$reportId, $token, $expiresAt])) {
        $_SESSION['public_access_token'] = $token;
        $_SESSION['token_evidence_id'] = $evidenceId;
        echo json_encode([
            'success' => true,
            'view_url' => "view-public-evidence.php?token=$token",
            'unlimited' => true
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create unlimited access token']);
    }
} else {
    // Regular token with 1 hour expiry
    $token = createAccessToken($conn, $reportId, $evidenceId, 'view_evidence');
    
    if ($token) {
        $_SESSION['public_access_token'] = $token;
        echo json_encode([
            'success' => true,
            'view_url' => "view-public-evidence.php?token=$token",
            'unlimited' => false
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create access token']);
    }
}