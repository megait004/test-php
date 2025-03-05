<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';

$auth = new Auth($conn);

// Nếu đã đăng nhập và là admin, chuyển đến dashboard
if ($auth->isAdmin()) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        if ($auth->login($email, $password)) {
            // Kiểm tra session sau khi login
            if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
                // Log the login activity
                require_once '../classes/ActivityLogger.php';
                $logger = new ActivityLogger($conn, $_SESSION['user_id'], $_SESSION['user_email']);
                $logger->log('login', 'Admin logged in successfully');

                header("Location: index.php");
                exit();
            } else {
                $_SESSION['error'] = "Tài khoản không có quyền admin";
                // Đảm bảo session được lưu trước khi redirect
                session_write_close();
                header("Location: login.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Email hoặc mật khẩu không đúng";
            // Đảm bảo session được lưu trước khi redirect
            session_write_close();
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Lỗi kết nối database: " . $e->getMessage();
        // Đảm bảo session được lưu trước khi redirect
        session_write_close();
        header("Location: login.php");
        exit();
    }
}

// Chỉ render HTML nếu không có redirect
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .admin-login-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #0061f2 0%, #00ba94 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .admin-login-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        .admin-login-header {
            background: rgba(0, 0, 0, 0.1);
            padding: 2rem;
            text-align: center;
        }
        .admin-login-header i {
            font-size: 3rem;
            color: #0061f2;
        }
        .admin-login-body {
            padding: 2rem;
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .form-floating label {
            color: #6c757d;
        }
        .btn-admin-login {
            background: linear-gradient(45deg, #0061f2, #00ba94);
            border: none;
            color: white;
            padding: 1rem;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
        }
        .btn-admin-login:hover {
            background: linear-gradient(45deg, #0056b3, #00a383);
            transform: translateY(-2px);
        }
        .back-to-site {
            color: #6c757d;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }
        .back-to-site:hover {
            color: #0061f2;
            transform: translateX(-5px);
        }
    </style>
</head>
<body class="admin-login-page">
    <div class="admin-login-card">
        <div class="admin-login-header">
            <i class="fas fa-user-shield mb-3"></i>
            <h4 class="mb-0">Admin Login</h4>
        </div>
        <div class="admin-login-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                    <label for="email">Email</label>
                </div>
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Mật khẩu</label>
                </div>
                <button type="submit" class="btn btn-admin-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="../index.php" class="back-to-site">
                    <i class="fas fa-arrow-left me-2"></i>
                    Quay lại trang chủ
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>