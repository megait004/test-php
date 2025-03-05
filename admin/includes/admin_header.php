<?php
require_once __DIR__ . '/../../classes/Notification.php';
$notification = new Notification($conn);
$notifications = $notification->getLatest($_SESSION['user_id']);
$unread_count = $notification->getUnreadCount($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-black">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-shield-alt me-2"></i>Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="fas fa-home me-1"></i>Trang chủ
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow" style="min-width: 300px;">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <h6 class="mb-0">Thông báo</h6>
                                <?php if ($unread_count > 0): ?>
                                    <button class="btn btn-link btn-sm text-decoration-none mark-all-read">
                                        Đánh dấu đã đọc
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="notifications-list" style="max-height: 300px; overflow-y: auto;">
                                <?php if (empty($notifications)): ?>
                                    <div class="p-3 text-center text-muted">
                                        Không có thông báo mới
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <a href="<?php echo $notif['link'] ?: '#'; ?>"
                                           class="dropdown-item notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>"
                                           data-id="<?php echo $notif['id']; ?>">
                                            <small class="text-muted d-block">
                                                <?php echo $notification->getTimeAgo($notif['created_at']); ?>
                                            </small>
                                            <?php echo htmlspecialchars($notif['message']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                    <div class="dropdown-divider"></div>
                                    <a href="notifications.php" class="dropdown-item text-center">
                                        Xem tất cả
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="../profile.php">
                                    <i class="fas fa-user me-2"></i>Hồ sơ
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="fas fa-cog me-2"></i>Cài đặt
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="../logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <style>
    .notification-item.unread {
        background-color: #e8f0fe;
    }
    .notification-item:hover {
        background-color: #f8f9fa;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Đánh dấu thông báo đã đọc khi click
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (!this.classList.contains('unread')) return;

                const notifId = this.dataset.id;
                fetch('mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'notification_id=' + notifId
                });

                this.classList.remove('unread');
                updateUnreadCount(-1);
            });
        });

        // Đánh dấu tất cả đã đọc
        const markAllReadBtn = document.querySelector('.mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                fetch('mark_all_notifications_read.php', {
                    method: 'POST'
                }).then(() => {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    updateUnreadCount(-9999); // Reset về 0
                });
            });
        }

        function updateUnreadCount(change) {
            const badge = document.querySelector('#notificationsDropdown .badge');
            if (!badge) return;

            let count = parseInt(badge.textContent) + change;
            if (count <= 0) {
                badge.remove();
            } else {
                badge.textContent = count;
            }
        }
    });
    </script>

<?php if (isset($_SESSION['success'])): ?>
    <div class="container-fluid mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="container-fluid mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
<?php endif; ?>
</body>
</html>