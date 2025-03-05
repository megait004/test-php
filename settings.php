<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';

$auth = new Auth($conn);
$auth->requireLogin();

$user_id = $_SESSION['user_id'];

// Lấy thông tin người dùng
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$success_message = '';
$error_message = '';

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);

        // Validate dữ liệu
        if (empty($full_name)) {
            $error_message = "Họ tên không được để trống";
        } else {
            try {
                // Kiểm tra email có bị trùng không (nếu thay đổi email)
                if ($email !== $user['email']) {
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $user_id]);
                    if ($stmt->fetchColumn() > 0) {
                        $error_message = "Email này đã được sử dụng";
                    }
                }

                if (empty($error_message)) {
                    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                    $stmt->execute([$full_name, $email, $user_id]);

                    // Cập nhật session
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['user_email'] = $email;

                    $success_message = "Đã cập nhật thông tin thành công!";

                    // Log the activity
                    require_once 'classes/ActivityLogger.php';
                    $logger = new ActivityLogger($conn, $user_id, $email);
                    $logger->log('update_profile', 'User updated profile information');
                }
            } catch (PDOException $e) {
                $error_message = "Có lỗi xảy ra: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate mật khẩu
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "Vui lòng điền đầy đủ thông tin mật khẩu";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "Mật khẩu mới không khớp";
        } elseif (!password_verify($current_password, $user['password'])) {
            $error_message = "Mật khẩu hiện tại không đúng";
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);

                $success_message = "Đã đổi mật khẩu thành công!";

                // Log the activity
                require_once 'classes/ActivityLogger.php';
                $logger = new ActivityLogger($conn, $user_id, $_SESSION['user_email']);
                $logger->log('change_password', 'User changed password');
            } catch (PDOException $e) {
                $error_message = "Có lỗi xảy ra: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt tài khoản - Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .settings-card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(33, 40, 50, 0.15);
        }
        .card-header {
            font-weight: 500;
            padding: 1rem 1.35rem;
            margin-bottom: 0;
            background-color: rgba(33, 40, 50, 0.03);
            border-bottom: 1px solid rgba(33, 40, 50, 0.125);
        }
        .avatar-upload {
            position: relative;
            max-width: 150px;
            margin: 20px auto;
        }
        .avatar-upload img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Thông tin cá nhân -->
                <div class="card settings-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-user-circle me-2"></i>
                        Thông tin cá nhân
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="avatar-upload mb-4">
                                <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower($user['email'])); ?>?s=150"
                                     alt="Avatar" class="img-fluid">
                            </div>

                            <div class="mb-3">
                                <label for="full_name" class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Lưu thay đổi
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Đổi mật khẩu -->
                <div class="card settings-card">
                    <div class="card-header">
                        <i class="fas fa-lock me-2"></i>
                        Đổi mật khẩu
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                                <input type="password" class="form-control" id="current_password"
                                       name="current_password" required>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">Mật khẩu mới</label>
                                <input type="password" class="form-control" id="new_password"
                                       name="new_password" required>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                                <input type="password" class="form-control" id="confirm_password"
                                       name="confirm_password" required>
                            </div>

                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key me-2"></i>Đổi mật khẩu
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>