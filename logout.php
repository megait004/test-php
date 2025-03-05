<?php
session_start();

// Log the logout activity
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    require_once 'classes/ActivityLogger.php';
    $logger = new ActivityLogger($conn, $_SESSION['user_id'], $_SESSION['user_email']);
    $logger->log('logout', 'User logged out');
}

// Xóa tất cả session
session_destroy();

// Chuyển hướng về trang chủ
header("Location: index.php");
exit();
?>