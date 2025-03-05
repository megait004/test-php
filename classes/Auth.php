<?php
class Auth {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare("
            SELECT u.*, r.name as role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role_name'];
            return true;
        }
        return false;
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin() {
        return isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'admin';
    }

    public function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit();
        }
    }

    public function requireAdmin() {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header("Location: ../login.php");
            exit();
        }
    }
}