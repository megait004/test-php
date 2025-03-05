<?php
// Cấu hình cơ bản
define('SITE_NAME', 'Hệ thống quản lý tài liệu');
define('SITE_URL', 'http://localhost/your-project'); // Thay đổi theo domain của bạn

// Cấu hình upload
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', [
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
]);

// Cấu hình email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('ADMIN_EMAIL', 'admin@example.com');

// Cấu hình phân trang
define('ITEMS_PER_PAGE', 10);