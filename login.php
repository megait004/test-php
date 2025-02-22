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

    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    $result = $user->login($username, $password);
    if($result) {
        $_SESSION['user'] = $result;
        if($result['role'] == 'Admin') {
            header('Location: admin/index.php');
        } else {
            header('Location: index.php');
        }
        exit();
    } else {
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="auth-container">
        <h2>Đăng nhập</h2>
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label>Tên đăng nhập:</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Mật khẩu:</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Đăng nhập</button>
        </form>

        <p style="text-align: center; margin-top: 1rem;">
            Chưa có tài khoản? <a href="register.php">Đăng ký</a>
        </p>
    </div>
</body>
</html>