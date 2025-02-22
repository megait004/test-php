<?php
session_start();
require_once 'config/Database.php';
require_once 'models/User.php';

if(isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $user = new User($db);

        if($user->register($username, $password)) {
            header('Location: login.php');
            exit();
        } else {
            $error = 'Đăng ký thất bại. Vui lòng thử lại.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Đăng ký</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#registerForm').submit(function(e) {
            var password = $('#password').val();
            var confirm = $('#confirm_password').val();

            if(password.length < 6) {
                alert('Mật khẩu phải có ít nhất 6 ký tự');
                e.preventDefault();
                return;
            }

            if(password !== confirm) {
                alert('Mật khẩu xác nhận không khớp');
                e.preventDefault();
                return;
            }
        });
    });
    </script>
</head>
<body>
    <div class="auth-container">
        <h2>Đăng ký tài khoản</h2>
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form id="registerForm" method="post" action="">
            <div class="form-group">
                <label>Tên đăng nhập:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu:</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group">
                <label>Xác nhận mật khẩu:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <button type="submit" class="btn">Đăng ký</button>
        </form>

        <p style="text-align: center; margin-top: 1rem;">
            Đã có tài khoản? <a href="login.php">Đăng nhập</a>
        </p>
    </div>
</body>
</html>