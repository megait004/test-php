<?php
class Notification {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function create($type, $message, $user_id = null, $link = null) {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (type, message, user_id, link)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$type, $message, $user_id, $link]);
    }

    public function getUnreadCount($user_id = null) {
        $sql = "SELECT COUNT(*) FROM notifications WHERE is_read = 0";
        $params = [];

        if ($user_id) {
            $sql .= " AND (user_id = ? OR user_id IS NULL)";
            $params[] = $user_id;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function getLatest($user_id = null, $limit = 5) {
        $sql = "SELECT * FROM notifications";
        $params = [];

        if ($user_id) {
            $sql .= " WHERE (user_id = ? OR user_id IS NULL)";
            $params[] = $user_id;
        }

        $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function markAsRead($notification_id) {
        $stmt = $this->conn->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE id = ?
        ");
        return $stmt->execute([$notification_id]);
    }

    public function markAllAsRead($user_id) {
        $stmt = $this->conn->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE (user_id = ? OR user_id IS NULL)
        ");
        return $stmt->execute([$user_id]);
    }

    public function getTimeAgo($timestamp) {
        $time = strtotime($timestamp);
        $current_time = time();
        $time_difference = $current_time - $time;

        if ($time_difference < 60) {
            return 'Vừa xong';
        } elseif ($time_difference < 3600) {
            $minutes = floor($time_difference / 60);
            return $minutes . ' phút trước';
        } elseif ($time_difference < 86400) {
            $hours = floor($time_difference / 3600);
            return $hours . ' giờ trước';
        } else {
            $days = floor($time_difference / 86400);
            return $days . ' ngày trước';
        }
    }
}