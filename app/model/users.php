<?php

function get_active_admins_email($conn) {
    $sql = "SELECT email, name FROM users WHERE role IN ('admin', 'super_admin') AND is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $admins;
}