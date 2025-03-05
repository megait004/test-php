<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Settings.php';

$auth = new Auth($conn);
$auth->requireAdmin();

$settings = Settings::getInstance($conn);
$success_message = '';
$error_message = '';

// Xử lý cập nhật cài đặt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Cài đặt người dùng
        $settings->set('allow_registration', $_POST['allow_registration'] ?? '0');
        $settings->set('require_email_verification', $_POST['require_email_verification'] ?? '0');
        $settings->set('default_user_role', $_POST['default_user_role'] ?? 'user');

        // Cài đặt bình luận
        $settings->set('allow_comments', $_POST['allow_comments'] ?? '0');
        $settings->set('moderate_comments', $_POST['moderate_comments'] ?? '0');
        $settings->set('spam_keywords', $_POST['spam_keywords'] ?? '');

        $success_message = "Đã cập nhật cài đặt thành công!";
    } catch (Exception $e) {
        $error_message = "Có lỗi xảy ra: " . $e->getMessage();
    }
}

// Lấy danh sách roles cho dropdown
$roles = $conn->query("SELECT * FROM roles ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt hệ thống - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Cài đặt hệ thống</h1>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-users me-2"></i>Cài đặt người dùng</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="allow_registration"
                                           name="allow_registration" value="1"
                                           <?php echo $settings->isRegistrationAllowed() ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="allow_registration">Cho phép đăng ký</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="require_email_verification"
                                           name="require_email_verification" value="1"
                                           <?php echo $settings->isEmailVerificationRequired() ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="require_email_verification">Yêu cầu xác thực email</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="default_user_role" class="form-label">Vai trò mặc định cho người dùng mới</label>
                                <select class="form-select" id="default_user_role" name="default_user_role">
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role['name']; ?>"
                                                <?php echo $settings->getDefaultUserRole() === $role['name'] ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($role['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-comments me-2"></i>Cài đặt bình luận</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="allow_comments"
                                           name="allow_comments" value="1"
                                           <?php echo $settings->areCommentsAllowed() ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="allow_comments">Cho phép bình luận</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="moderate_comments"
                                           name="moderate_comments" value="1"
                                           <?php echo $settings->areCommentsModerated() ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="moderate_comments">Kiểm duyệt bình luận</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="spam_keywords" class="form-label">Từ khóa spam (phân cách bằng dấu phẩy)</label>
                                <textarea class="form-control" id="spam_keywords" name="spam_keywords" rows="3"
                                          placeholder="Ví dụ: sex, casino, gambling"><?php echo $settings->get('spam_keywords'); ?></textarea>
                                <div class="form-text">Các bình luận chứa những từ khóa này sẽ bị đánh dấu là spam</div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Lưu cài đặt
                        </button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>