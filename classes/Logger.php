<?php
class Logger {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function log($action, $description, $user_id = null) {
        if ($user_id === null && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        return $stmt->execute([
            $user_id,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR']
        ]);
    }
}