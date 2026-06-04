<?php
// app/get-report-details.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/model/index.php';

$reportId = isset($_GET['report_id']) ? (int)$_GET['report_id'] : 0;

if (!$reportId) {
    echo json_encode(['success' => false, 'error' => 'Report ID required']);
    exit;
}

// Get report details (only public approved reports)
$report = get_approved_reports_details($conn, $reportId);

if (!$report) {
    echo json_encode(['success' => false, 'error' => 'Report not found or not public']);
    exit;
}

// Decrypt description
$description = decryptData($report['encrypted_description'], ENCRYPTION_KEY);

// Get public evidences
$evidences = getEvidences($conn, $reportId, true);

// Format evidences for response
$evidencesList = [];
foreach ($evidences as $evidence) {
    $evidencesList[] = [
        'id' => $evidence['id'],
        'size_bytes' => $evidence['size_bytes'],
        'mime_type' => $evidence['mime_type'],
        'created_at' => $evidence['created_at']
    ];
}

echo json_encode([
    'success' => true,
    'id' => $report['id'],
    'category' => $report['category_name'],
    'description' => $description,
    'status' => $report['status'],
    'priority' => $report['priority'],
    'created_at' => date('F j, Y g:i A', strtotime($report['created_at'])),
    'evidences' => $evidencesList
]);