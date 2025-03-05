<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Settings.php';
require_once 'classes/Notification.php';

$settings = Settings::getInstance($conn);
$notification = new Notification($conn);

// Kiểm tra xem có cho phép đăng ký không
if (!$settings->isRegistrationAllowed()) {
    $_SESSION['error'] = "Đăng ký tài khoản tạm thời bị vô hiệu hóa.";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';

    $errors = [];

    // Kiểm tra email đã tồn tại
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Email đã được sử dụng";
    }

    // Kiểm tra mật khẩu
    if ($password !== $confirm_password) {
        $errors[] = "Mật khẩu xác nhận không khớp";
    }

    if (empty($errors)) {
        try {
            // Lấy role_id của user từ bảng roles
            $stmt = $conn->prepare("SELECT id FROM roles WHERE name = ?");
            $stmt->execute([$settings->getDefaultUserRole()]);
            $role = $stmt->fetch();

            if (!$role) {
                throw new Exception("Không tìm thấy role mặc định");
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $status = $settings->isEmailVerificationRequired() ? 'inactive' : 'active';

            $stmt = $conn->prepare("INSERT INTO users (email, password, role_id, full_name, status) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$email, $hashed_password, $role['id'], $full_name, $status])) {
                // Tạo thông báo cho admin về người dùng mới
                $notification->create(
                    'new_user',
                    "Người dùng mới đăng ký: " . $full_name,
                    null, // null để tất cả admin đều nhận được
                    "admin/users.php"
                );

                if ($settings->isEmailVerificationRequired()) {
                    $_SESSION['success'] = "Đăng ký thành công! Vui lòng kiểm tra email để xác thực tài khoản.";
                    // TODO: Gửi email xác thực
                } else {
                    $_SESSION['success'] = "Đăng ký thành công! Vui lòng đăng nhập.";
                }
                header("Location: login.php");
                exit();
            } else {
                $errors[] = "Có lỗi xảy ra, vui lòng thử lại";
            }
        } catch (Exception $e) {
            $errors[] = "Có lỗi xảy ra: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Đăng ký tài khoản</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mật khẩu</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Đăng ký</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <a href="login.php">Đã có tài khoản? Đăng nhập</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>