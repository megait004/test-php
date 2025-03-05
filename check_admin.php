<?php
require_once 'config/database.php';

try {
    echo "<h3>Kiểm tra database:</h3>";

    // 1. Kiểm tra bảng roles
    $stmt = $conn->query("SELECT * FROM roles");
    $roles = $stmt->fetchAll();

    echo "<h4>1. Danh sách roles:</h4>";
    echo "<pre>";
    print_r($roles);
    echo "</pre>";

    // 2. Kiểm tra bảng users
    $stmt = $conn->query("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id");
    $users = $stmt->fetchAll();

    echo "<h4>2. Danh sách users:</h4>";
    echo "<pre>";
    print_r($users);
    echo "</pre>";

} catch (Exception $e) {
    echo "<div style='color: red;'>";
    echo "Lỗi: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}