<?php
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

function fetch_evidences($conn, $reportId){
    $sql = "SELECT * FROM evidences WHERE report_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reportId]);
    $evidences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $evidences;
}

function fetch_report_evidence($conn, $reportId){
    $sql = "SELECT * FROM evidences WHERE report_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$reportId]);
    $evidences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $evidences;
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

function get_public_evidence_by_id($conn, $data, $unlimited){
    $sql = "SELECT id FROM evidences 
    WHERE id = ? AND report_id = ? AND is_public = TRUE
    AND (expires_at IS NULL OR expires_at > NOW())";
    if (!$unlimited) {
        $sql .= " AND (max_views IS NULL OR view_count < max_views)";
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
    $evidence = $stmt->fetch(PDO::FETCH_ASSOC);
    return $evidence;
}
?>