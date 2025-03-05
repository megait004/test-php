<?php
class Middleware {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /shop/login.php");
            exit();
        }

        // Kiểm tra trạng thái tài khoản
        $stmt = $this->conn->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active') {
            session_destroy();
            header("Location: /shop/login.php?error=account_inactive");
            exit();
        }
    }

    public function checkAdmin() {
        $this->checkAuth();

        if ($_SESSION['user_role'] !== 'admin') {
            header("Location: /shop/index.php?error=unauthorized");
            exit();
        }
    }

    public function checkPermission($permission) {
        $this->checkAuth();

        if ($_SESSION['user_role'] === 'admin') {
            return true;
        }

        $stmt = $this->conn->prepare("
            SELECT 1
            FROM role_permissions rp
            JOIN users u ON u.role_id = rp.role_id
            WHERE u.id = ? AND rp.permission = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $permission]);

        if ($stmt->rowCount() === 0) {
            header("Location: /shop/index.php?error=permission_denied");
            exit();
        }
    }
}