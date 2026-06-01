<?php
// app/create-public-access-token.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/model/report.php';

$reportId = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;
$evidenceId = isset($_POST['evidence_id']) ? (int)$_POST['evidence_id'] : 0;

if (!$reportId || !$evidenceId) {
    echo json_encode(['success' => false, 'error' => 'Report ID and Evidence ID required']);
    exit;
}

// Verify report is public and approved
$stmt = $conn->prepare("
    SELECT id FROM reports 
    WHERE id = ? AND visibility = 'public' 
    AND publication_status = 'approved'
    AND (expires_at IS NULL OR expires_at > NOW())
");
$stmt->execute([$reportId]);
$report = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$report) {
    echo json_encode(['success' => false, 'error' => 'Report not found or not public']);
    exit;
}

// Verify evidence exists and is public
$stmt = $conn->prepare("
    SELECT id FROM evidences 
    WHERE id = ? AND report_id = ? AND is_public = TRUE
    AND (expires_at IS NULL OR expires_at > NOW())
    AND (max_views IS NULL OR view_count < max_views)
");
$stmt->execute([$evidenceId, $reportId]);
$evidence = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evidence) {
    echo json_encode(['success' => false, 'error' => 'Evidence not found or not public']);
    exit;
}

// Create access token
$token = createAccessToken($conn, $reportId, $evidenceId, 'view_evidence');

if ($token) {
    $_SESSION['public_access_token'] = $token;
    echo json_encode([
        'success' => true,
        'view_url' => "view-public-evidence.php?token=$token"
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to create access token']);
}