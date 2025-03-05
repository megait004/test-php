<?php
require_once '../config/database.php';
require_once '../classes/Notification.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification = new Notification($conn);
    $notification->markAllAsRead($_SESSION['user_id']);
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request']);
}