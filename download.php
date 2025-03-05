<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Lấy ID tài liệu
$document_id = $_GET['id'] ?? 0;

// Kiểm tra và lấy thông tin tài liệu
$stmt = $conn->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$document_id]);
$document = $stmt->fetch();

if (!$document || !$document['download_link'] || !file_exists($document['download_link'])) {
    $_SESSION['error'] = "Không tìm thấy tài liệu hoặc file đã bị xóa!";
    header("Location: index.php");
    exit();
}

// Lấy thông tin file
$file_path = $document['download_link'];
$file_name = basename($file_path);
$file_size = filesize($file_path);
$file_type = mime_content_type($file_path);

// Thiết lập headers cho việc download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Length: ' . $file_size);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Đọc và xuất file
readfile($file_path);
exit();