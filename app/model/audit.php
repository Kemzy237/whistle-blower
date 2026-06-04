<?php
function log_system_action($conn, $table, $data) {
    $columns = array_keys($data);
    $columnString = '`' . implode('`, `', $columns) . '`';
    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
    $sql = "INSERT INTO `{$table}` ({$columnString}) VALUES ({$placeholders})";
    try {
        $stmt = $conn->prepare($sql);
        return $stmt->execute(array_values($data));
    } catch (PDOException $e) {
        error_log("Dynamic logging failed: " . $e->getMessage());
        return false;
    }
}

function get_system_stats($conn){
    $sql = "SELECT 
        COUNT(*) as total_reports,
        COUNT(DISTINCT anonymous_session_id) as unique_whistleblowers,
        (SELECT COUNT(*) FROM messages) as total_messages,
        (SELECT COUNT(*) FROM evidences) as total_evidences,
        (SELECT COUNT(*) FROM users WHERE role IN ('admin', 'super_admin')) as total_admins
    FROM reports";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $systemStats = $stmt->fetch(PDO::FETCH_ASSOC);
    return $systemStats;
}

function get_database_size($conn){
    $sql = "SELECT 
        table_schema as 'database',
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
    FROM information_schema.tables 
    WHERE table_schema = 'whistleblower'
    GROUP BY table_schema";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $size = $stmt->fetch(PDO::FETCH_ASSOC);
    return $size;
}

function count_unread_reports($conn){
    $sql = "SELECT COUNT(DISTINCT r.id) as count
    FROM reports r
    JOIN messages m ON r.id = m.report_id
    WHERE m.sender_type = 'whistleblower' 
    AND m.is_read = FALSE
    AND r.status != 'closed'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result["count"];
}

function count_user_activity($conn, $userId) {
    $sql = 'SELECT COUNT(*) as total_actions FROM audit_logs WHERE admin_user_id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->execute([$userId]);
    $totalActions = $stmt->fetch(PDO::FETCH_ASSOC)['total_actions'];
    return $totalActions;
}

function get_recent_activity($conn, $adminId){
    $sql = "SELECT action, COUNT(*) as count 
    FROM audit_logs 
    WHERE admin_user_id = ? 
    GROUP BY action 
    ORDER BY count DESC 
    LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$adminId]);
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $recentActivities;
}

function insert_anonymous_session($conn, $data){
        $sql = "INSERT INTO anonymous_sessions (session_id, ip_hash, user_agent_hash, report_count, last_activity)
        VALUES (?, ?, ?, 1, NOW())
        ON DUPLICATE KEY UPDATE
            report_count = report_count + 1,
            last_activity = NOW(),
            ip_hash = VALUES(ip_hash),
            user_agent_hash = VALUES(user_agent_hash)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);
    }
?>