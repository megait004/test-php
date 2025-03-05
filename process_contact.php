<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lấy dữ liệu từ form
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';

    // Validate dữ liệu
    $errors = [];
    if (empty($name)) {
        $errors[] = "Họ tên không được để trống";
    }
    if (empty($email)) {
        $errors[] = "Email không được để trống";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ";
    }
    if (empty($subject)) {
        $errors[] = "Chủ đề không được để trống";
    }
    if (empty($message)) {
        $errors[] = "Nội dung không được để trống";
    }

    // Nếu có lỗi
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: contact.php");
        exit();
    }

    // Gửi email (trong ví dụ này chỉ là mô phỏng)
    $to = "info@example.com";
    $email_subject = "Liên hệ mới: " . $subject;
    $email_body = "Bạn nhận được một liên hệ mới từ website.\n\n" .
        "Họ tên: $name\n" .
        "Email: $email\n" .
        "Nội dung:\n$message";
    $headers = "From: $email";

    if (mail($to, $email_subject, $email_body, $headers)) {
        $_SESSION['success'] = "Cảm ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi sớm nhất có thể.";
    } else {
        $_SESSION['errors'] = ["Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại sau."];
    }

    header("Location: contact.php");
    exit();
} else {
    // Nếu không phải POST request, chuyển hướng về trang liên hệ
    header("Location: contact.php");
    exit();
}