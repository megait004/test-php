<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Auth.php';

$auth = new Auth($conn);
$auth->requireLogin();

$user_id = $_SESSION['user_id'];

// Lấy thông tin người dùng
$stmt = $conn->prepare("
    SELECT u.*,
           COUNT(DISTINCT d.id) as total_documents,
           COUNT(DISTINCT c.id) as total_comments,
           COUNT(DISTINCT l.id) as total_likes
    FROM users u
    LEFT JOIN documents d ON u.id = d.user_id
    LEFT JOIN comments c ON u.id = c.user_id
    LEFT JOIN likes l ON u.id = l.user_id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $errors = [];

    // Cập nhật tên
    if (!empty($full_name) && $full_name !== $user['full_name']) {
        $stmt = $conn->prepare("UPDATE users SET full_name = ? WHERE id = ?");
        $stmt->execute([$full_name, $user_id]);
        $_SESSION['user_name'] = $full_name;
        $_SESSION['success'] = "Đã cập nhật thông tin thành công!";
    }

    // Cập nhật mật khẩu
    if (!empty($current_password)) {
        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                $_SESSION['success'] = "Đã cập nhật mật khẩu thành công!";
            } else {
                $errors[] = "Mật khẩu mới không khớp";
            }
        } else {
            $errors[] = "Mật khẩu hiện tại không đúng";
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }

    header("Location: profile.php");
    exit();
}

// Lấy hoạt động gần đây của người dùng
$recent_activities = $conn->prepare("
    (SELECT 'document' as type, d.title as content, d.created_at
     FROM documents d
     WHERE d.user_id = ?)
    UNION ALL
    (SELECT 'comment' as type, c.content as content, c.created_at
     FROM comments c
     WHERE c.user_id = ?)
    UNION ALL
    (SELECT 'like' as type, d.title as content, l.created_at
     FROM likes l
     JOIN documents d ON l.document_id = d.id
     WHERE l.user_id = ?)
    ORDER BY created_at DESC
    LIMIT 10
");
$recent_activities->execute([$user_id, $user_id, $user_id]);
$activities = $recent_activities->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân - <?php echo htmlspecialchars($user['full_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .sticky-sidebar {
            position: sticky;
            top: 20px;
            z-index: 1000;
        }
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline-item {
            position: relative;
            padding: 15px 0;
            border-left: 2px solid #e9ecef;
            margin-left: 20px;
        }
        .timeline-icon {
            position: absolute;
            left: -11px;
            width: 20px;
            height: 20px;
            background: #fff;
            border-radius: 50%;
            text-align: center;
            line-height: 20px;
            color: #007bff;
            border: 2px solid #007bff;
        }
        .timeline-icon i {
            font-size: 12px;
        }
        .timeline-content {
            margin-left: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .timeline-content p {
            margin-bottom: 5px;
        }
        .profile-stats {
            padding: 20px 0;
            border-top: 1px solid #eee;
            margin-top: 20px;
        }
        .profile-stats .stat-item {
            text-align: center;
        }
        .profile-stats .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .profile-stats .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        .profile-avatar {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <!-- Thông tin cá nhân -->
            <div class="col-md-4">
                <div class="card sticky-sidebar">
                    <div class="card-body text-center">
                        <div class="profile-avatar mb-4">
                            <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower($user['email'])); ?>?s=150"
                                 class="rounded-circle" alt="Avatar">
                        </div>
                        <h4 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <p class="text-muted mb-4"><?php echo htmlspecialchars($user['email']); ?></p>

                        <div class="profile-stats">
                            <div class="row">
                                <div class="col-4 stat-item">
                                    <div class="stat-value"><?php echo $user['total_documents']; ?></div>
                                    <div class="stat-label">Tài liệu</div>
                                </div>
                                <div class="col-4 stat-item">
                                    <div class="stat-value"><?php echo $user['total_comments']; ?></div>
                                    <div class="stat-label">Bình luận</div>
                                </div>
                                <div class="col-4 stat-item">
                                    <div class="stat-value"><?php echo $user['total_likes']; ?></div>
                                    <div class="stat-label">Thích</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cập nhật thông tin -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Cập nhật thông tin</h5>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Họ và tên</label>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                       value="<?php echo htmlspecialchars($user['full_name']); ?>">
                            </div>

                            <h5 class="mt-4">Đổi mật khẩu</h5>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Mật khẩu mới</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>

                            <button type="submit" class="btn btn-primary">Cập nhật</button>
                        </form>
                    </div>
                </div>

                <!-- Hoạt động gần đây -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Hoạt động gần đây</h5>
                        <div class="timeline">
                            <?php foreach ($activities as $activity): ?>
                                <div class="timeline-item">
                                    <div class="timeline-icon">
                                        <?php if ($activity['type'] === 'document'): ?>
                                            <i class="fas fa-file-alt"></i>
                                        <?php elseif ($activity['type'] === 'comment'): ?>
                                            <i class="fas fa-comment"></i>
                                        <?php else: ?>
                                            <i class="fas fa-heart"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="timeline-content">
                                        <p>
                                            <?php
                                            switch ($activity['type']) {
                                                case 'document':
                                                    echo "Đã tải lên tài liệu: ";
                                                    break;
                                                case 'comment':
                                                    echo "Đã bình luận: ";
                                                    break;
                                                case 'like':
                                                    echo "Đã thích tài liệu: ";
                                                    break;
                                            }
                                            echo htmlspecialchars($activity['content']);
                                            ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>