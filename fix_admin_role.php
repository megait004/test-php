<?php
require_once 'config/database.php';

try {
    // Lấy email từ parameter
    $email = isset($_GET['email']) ? $_GET['email'] : '';

    if (empty($email)) {
        die("Vui lòng cung cấp email qua parameter ?email=your_email@example.com");
    }

    $conn->beginTransaction();

    // Kiểm tra role admin có tồn tại không
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

    // Cập nhật quyền admin cho user
    $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE email = ?");
    if ($stmt->execute([$role_id, $email])) {
        // Kiểm tra xem có user nào được update không
        if ($stmt->rowCount() > 0) {
            $conn->commit();
            echo "<div style='color: green; padding: 20px; border: 1px solid green; margin: 20px;'>";
            echo "✅ Đã cập nhật thành công quyền admin cho tài khoản: " . htmlspecialchars($email);
            echo "<br><br>Bây giờ bạn có thể:<br>";
            echo "1. <a href='admin/login.php'>Đăng nhập trang admin</a><br>";
            echo "2. Xóa file fix_admin_role.php này sau khi hoàn tất";
            echo "</div>";
        } else {
            $conn->rollBack();
            echo "<div style='color: red; padding: 20px; border: 1px solid red; margin: 20px;'>";
            echo "❌ Không tìm thấy tài khoản với email: " . htmlspecialchars($email);
            echo "</div>";
        }
    } else {
        throw new Exception("Có lỗi xảy ra khi cập nhật quyền");
    }
} catch (Exception $e) {
    $conn->rollBack();
    echo "<div style='color: red; padding: 20px; border: 1px solid red; margin: 20px;'>";
    echo "❌ Lỗi: " . htmlspecialchars($e->getMessage());
    echo "</div>";
}