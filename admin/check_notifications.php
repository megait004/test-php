<?php
require_once '../config/database.php';
require_once '../classes/Notification.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$notification = new Notification($conn);
$unread_count = $notification->getUnreadCount($_SESSION['user_id']);
$latest = $notification->getLatest($_SESSION['user_id'], 5);

echo json_encode([
    'unread_count' => $unread_count,
    'notifications' => array_map(function($notif) use ($notification) {
        return [
            'id' => $notif['id'],
            'message' => $notif['message'],
            'link' => $notif['link'],
            'is_read' => $notif['is_read'],
            'time_ago' => $notification->getTimeAgo($notif['created_at'])
        ];
    }, $latest)
]);