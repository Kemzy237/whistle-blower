<?php
// view-evidence.php
session_start();

require_once __DIR__ . '/db_connection.php';
include "app/model/report.php";

function decryptFilePath($encryptedPath, $key) {
    return decryptData($encryptedPath, $key);
}

// 1. DETERMINE ACCESS ROUTE: Check if the user is a logged-in Administrator first
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

$evidence = null;

if ($isAdmin) {
    // Admins bypass tokens completely. Grab the target file ID directly from the URL.
    $evidenceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($evidenceId > 0) {
        $evidence = get_evidence_by_id($conn, $evidenceId);
    }
    
    if (!$evidence) {
        die('<html><head><title>Not Found</title><style>body{background:#0a0e1a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;}</style></head><body><div style="text-align:center"><h1>Evidence Not Found</h1><p>The requested evidence could not be found or has been deleted.</p><a href="admin-reports.php" style="color:#4f46e5;">Return to Dashboard</a></div></body></html>');
    }

} else {
    // 2. PUBLIC/ANONYMOUS ROUTE: Fallback to token validation
    $token = $_SESSION['access_token'] ?? $_GET['token'] ?? '';

    if (empty($token)) {
        die('<html><head><title>Access Denied</title><style>body{background:#0a0e1a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;}</style></head><body><div style="text-align:center"><h1>Access Denied</h1><p>No access token provided.</p><a href="track-report.php" style="color:#4f46e5;">Return to Track Report</a></div></body></html>');
    }

    // Validate access token
    $accessToken = get_access_token($conn, $token);

    if (!$accessToken) {
        unset($_SESSION['access_token']);
        unset($_SESSION['evidence_id_to_view']);
        die('<html><head><title>Access Expired</title><style>body{background:#0a0e1a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;}</style></head><body><div style="text-align:center"><h1>Access Token Expired</h1><p>The access link has expired or has already been used. Tokens are valid for 1 hour and can only be used once.</p><a href="track-report.php" style="color:#4f46e5;">Return to Track Report</a></div></body></html>');
    }

    // Mark token as used
    mark_token_as_read($conn, $accessToken['id']);

    // Clear session token
    unset($_SESSION['access_token']);

    // Get evidence context for public user
    $evidenceId = $_SESSION['evidence_id_to_view'] ?? null;
    unset($_SESSION['evidence_id_to_view']);

    if ($evidenceId) {
        $evidence = get_evidence_by_id($conn, $evidenceId);
    } else {
        // Fallback: get latest evidence for this report
        $evidence = get_latest_report_evidence($conn, $accessToken);
    }

    if (!$evidence) {
        die('<html><head><title>Not Found</title><style>body{background:#0a0e1a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;}</style></head><body><div style="text-align:center"><h1>Evidence Not Found</h1><p>The requested evidence could not be found or has been deleted.</p><a href="track-report.php" style="color:#4f46e5;">Return to Track Report</a></div></body></html>');
    }

    // Update view count only for public views (Optional: keeps metrics clean from admin interference)
    update_view_count($conn, $evidence['id']);
}

// 3. RETRIEVE AND DISPATCH FILE (Shared by both Admin and User)
$filePath = decryptFilePath($evidence['encrypted_file_path'], ENCRYPTION_KEY);
$fileName = decryptData($evidence['encrypted_name'], ENCRYPTION_KEY);

if (!file_exists($filePath)) {
    $redirectUrl = $isAdmin ? "admin-reports.php" : "track-report.php";
    die('<html><head><title>File Not Found</title><style>body{background:#0a0e1a;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;}</style></head><body><div style="text-align:center"><h1>File Not Found</h1><p>The evidence file could not be located on the server.</p><a href="' . $redirectUrl . '" style="color:#4f46e5;">Return Back</a></div></body></html>');
}

// Read and decrypt file content
$encryptedContent = file_get_contents($filePath);
$fileContent = decryptData($encryptedContent, ENCRYPTION_KEY);

// Set headers for download/inline display
$mimeType = $evidence['mime_type'];
$disposition = strpos($mimeType, 'image/') === 0 ? 'inline' : 'attachment';

header('Content-Type: ' . $mimeType);
header('Content-Disposition: ' . $disposition . '; filename="' . $fileName . '"');
header('Content-Length: ' . strlen($fileContent));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $fileContent;
exit;
?>