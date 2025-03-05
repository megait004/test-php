<?php
class ActivityLogger {
    private $conn;
    private $user_id;
    private $user_email;

    public function __construct($conn, $user_id = null, $user_email = null) {
        $this->conn = $conn;
        $this->user_id = $user_id;
        $this->user_email = $user_email;
    }

    public function log($action, $details = '') {
        $stmt = $this->conn->prepare("
            INSERT INTO activities (user_id, user_email, action, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

        return $stmt->execute([
            $this->user_id,
            $this->user_email,
            $action,
            $details,
            $ip
        ]);
    }

    public function getActivities($limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT * FROM activities
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");

        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserActivities($user_id, $limit = 10, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT * FROM activities
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");

        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActivityCount() {
        $stmt = $this->conn->query("SELECT COUNT(*) FROM activities");
        return $stmt->fetchColumn();
    }

    public function getUserActivityCount($user_id) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM activities WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }
}