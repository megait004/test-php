<?php
require_once 'config/database.php';

// Thông tin admin mặc định
$email = 'admin@example.com';
$password = 'admin123'; // Sẽ được hash
$fullName = 'System Administrator';

try {
    $conn->beginTransaction();

    // Kiểm tra xem role admin đã tồn tại chưa
    $stmt = $conn->prepare("SELECT id FROM roles WHERE name = 'admin'");
    $stmt->execute();
    $role = $stmt->fetch();

    if (!$role) {
        // Tạo role admin nếu chưa có
        $stmt = $conn->prepare("INSERT INTO roles (name) VALUES ('admin')");
        $stmt->execute();
        $role_id = $conn->lastInsertId();
    } else {
        $role_id = $role['id'];
    }

    // Kiểm tra xem email đã tồn tại chưa
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        die("Email này đã được sử dụng!");
    }

    // Hash mật khẩu
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Thêm admin mới
    $stmt = $conn->prepare("
        INSERT INTO users (email, password, full_name, role_id, status)
        VALUES (?, ?, ?, ?, 'active')
    ");

    if ($stmt->execute([$email, $hashedPassword, $fullName, $role_id])) {
        $conn->commit();
        echo "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<h2 style='color: #28a745;'>✅ Đã tạo tài khoản admin thành công!</h2>";
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>Thông tin đăng nhập:</h3>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
        echo "<p><strong>Mật khẩu:</strong> " . htmlspecialchars($password) . "</p>";
        echo "</div>";
        echo "<div style='color: #dc3545; margin-top: 20px;'>";
        echo "⚠️ Vui lòng lưu thông tin này và <strong>xóa file create_admin.php</strong> sau khi sử dụng.";
        echo "</div>";
        echo "<div style='margin-top: 20px;'>";
        echo "<a href='admin/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Đăng nhập trang Admin</a>";
        echo "</div>";
        echo "</div>";
    } else {
        throw new Exception("Có lỗi xảy ra khi tạo tài khoản admin.");
    }
} catch (Exception $e) {
    $conn->rollBack();
    echo "<div style='color: #dc3545; padding: 20px;'>";
    echo "❌ Lỗi: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
