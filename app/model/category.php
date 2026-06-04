<?php
function insert_category($conn, $data) {
    $sql = 'INSERT INTO categories (name, description, is_active, created_at) VALUES (?, ?, 1, NOW())';
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
}

function update_category($conn, $data) {
    $sql = 'UPDATE categories SET name = ?, description = ?, is_active = ?, updated_at = NOW() WHERE id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->execute($data);
}

function count_category_reports($conn, $categoryId) {
    $sql = 'SELECT COUNT(*) as count FROM reports WHERE category_id = ?';
    $stmt = $conn->prepare($sql);
    $stmt->execute([$categoryId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['count'] : 0;
}

function delete_category($conn, $categoryId) {
    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$categoryId]);
}

function get_ordered_categories($conn) {
    $sql = "SELECT * FROM categories ORDER BY id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}
?>