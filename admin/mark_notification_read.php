<?php
require_once '../config/database.php';
require_once '../classes/Notification.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notification_id'])) {
    $notification = new Notification($conn);
    $notification_id = intval($_POST['notification_id']);

    // Kiểm tra quyền sở hữu thông báo
    $stmt = $conn->prepare("SELECT user_id FROM notifications WHERE id = ?");
    $stmt->execute([$notification_id]);
    $notif = $stmt->fetch();

    if ($notif && $notif['user_id'] == $_SESSION['user_id']) {
        $notification->markAsRead($notification_id);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request']);
}