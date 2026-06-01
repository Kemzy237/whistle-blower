<?php
// view-public-evidence.php
session_start();

require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/app/model/report.php';

$token = $_GET['token'] ?? $_SESSION['public_access_token'] ?? '';

if (empty($token)) {
    die('<html><head><title>Access Denied</title><style>body{background:#0a0e1a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;}</style></head><body><div style="text-align:center"><h1>Access Denied</h1><p>No access token provided.</p><a href="dashboard.php" style="color:#4f46e5;">Return to Dashboard</a></div></body></html>');
}

// Clear session token
unset($_SESSION['public_access_token']);

// Validate access token (allow used tokens for unlimited access)
$stmt = $conn->prepare("
    SELECT id, report_id, token, type, expires_at, used_at 
    FROM access_tokens 
    WHERE token = ? AND type = 'view_evidence' AND expires_at > NOW()
");
$stmt->execute([$token]);
$accessToken = $stmt->fetch(PDO::FETCH_ASSOC);

// For unlimited tokens, allow even if used
$isUnlimited = false;
if (!$accessToken) {
    // Check if token exists but was used (unlimited access)
    $stmt = $conn->prepare("
        SELECT id, report_id, token, type, expires_at, used_at 
        FROM access_tokens 
        WHERE token = ? AND type = 'view_evidence' AND expires_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
    ");
    $stmt->execute([$token]);
    $accessToken = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($accessToken && $accessToken['used_at'] !== null) {
        $isUnlimited = true; // Allow reuse for unlimited tokens
    }
}

if (!$accessToken) {
    die('<html><head><title>Access Expired</title><style>body{background:#0a0e1a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;}</style></head><body><div style="text-align:center"><h1>Access Token Expired</h1><p>The access link has expired.</p><a href="dashboard.php" style="color:#4f46e5;">Return to Dashboard</a></div></body></html>');
}

// Only mark as used if not unlimited
if (!$isUnlimited && $accessToken['used_at'] === null) {
    $stmt = $conn->prepare("UPDATE access_tokens SET used_at = NOW() WHERE id = ?");
    $stmt->execute([$accessToken['id']]);
}

// Get evidence
$evidenceId = $_SESSION['token_evidence_id'] ?? null;
unset($_SESSION['token_evidence_id']);

if ($evidenceId) {
    $stmt = $conn->prepare("
        SELECT e.*, r.tracking_code 
        FROM evidences e 
        JOIN reports r ON e.report_id = r.id 
        WHERE e.id = ? AND e.is_public = TRUE
        AND r.visibility = 'public' AND r.publication_status = 'approved'
    ");
    $stmt->execute([$evidenceId]);
    $evidence = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $stmt = $conn->prepare("
        SELECT e.*, r.tracking_code 
        FROM evidences e 
        JOIN reports r ON e.report_id = r.id 
        WHERE e.report_id = ? AND e.is_public = TRUE
        AND r.visibility = 'public' AND r.publication_status = 'approved'
        ORDER BY e.id DESC LIMIT 1
    ");
    $stmt->execute([$accessToken['report_id']]);
    $evidence = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$evidence) {
    die('<html><head><title>Not Found</title><style>body{background:#0a0e1a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;}</style></head><body><div style="text-align:center"><h1>Evidence Not Found</h1><p>The requested evidence could not be found or is not public.</p><a href="dashboard.php" style="color:#4f46e5;">Return to Dashboard</a></div></body></html>');
}

// Update view count (always increment, even for unlimited)
$stmt = $conn->prepare("UPDATE evidences SET view_count = view_count + 1, updated_at = NOW() WHERE id = ?");
$stmt->execute([$evidence['id']]);

// Decrypt file path and retrieve file
$filePath = decryptData($evidence['encrypted_file_path'], ENCRYPTION_KEY);
$fileName = decryptData($evidence['encrypted_name'], ENCRYPTION_KEY);

if (!file_exists($filePath)) {
    die('<html><head><title>File Not Found</title><style>body{background:#0a0e1a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;}</style></head><body><div style="text-align:center"><h1>File Not Found</h1><p>The evidence file could not be located.</p><a href="dashboard.php" style="color:#4f46e5;">Return to Dashboard</a></div></body></html>');
}

// Read and decrypt file content
$encryptedContent = file_get_contents($filePath);
$fileContent = decryptData($encryptedContent, ENCRYPTION_KEY);

// Set headers for download/inline display
$mimeType = $evidence['mime_type'];
$disposition = strpos($mimeType, 'image/') === 0 ? 'inline' : 'attachment';

header('Content-Type: ' . $mimeType);
header('Content-Disposition: ' . $disposition . '; filename="public_' . $fileName . '"');
header('Content-Length: ' . strlen($fileContent));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $fileContent;
exit;