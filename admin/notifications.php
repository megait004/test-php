<?php
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Notification.php';

$auth = new Auth($conn);
$auth->requireAdmin();

$notification = new Notification($conn);
$notifications = $notification->getLatest($_SESSION['user_id'], 50); // Lấy 50 thông báo gần nhất

include 'includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Thông báo</h1>
                <?php if (!empty($notifications)): ?>
                    <button class="btn btn-outline-primary mark-all-read">
                        <i class="fas fa-check-double me-1"></i>Đánh dấu tất cả đã đọc
                    </button>
                <?php endif; ?>
            </div>

            <div class="notifications-list">
                <?php if (!empty($notifications)): ?>
                    <?php foreach ($notifications as $notif): ?>
                        <div class="card mb-3 notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>"
                             data-id="<?php echo $notif['id']; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">
                                        <?php echo $notification->getTimeAgo($notif['created_at']); ?>
                                    </small>
                                    <?php if (!$notif['is_read']): ?>
                                        <span class="badge bg-primary">Mới</span>
                                    <?php endif; ?>
                                </div>
                                <p class="card-text mb-2">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </p>
                                <?php if ($notif['link']): ?>
                                    <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-1"></i>Xem chi tiết
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Không có thông báo nào
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<style>
.notification-item {
    transition: all 0.3s ease;
}

.notification-item.unread {
    background-color: #e8f0fe;
    border-left: 4px solid #1a73e8;
}

.notification-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Đánh dấu thông báo đã đọc khi click
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            const notifId = this.dataset.id;
            if (!this.classList.contains('unread')) return;

            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notification_id=' + notifId
            }).then(() => {
                this.classList.remove('unread');
                const badge = this.querySelector('.badge.bg-primary');
                if (badge) badge.remove();
            });
        });
    });

    // Đánh dấu tất cả đã đọc
    const markAllReadBtn = document.querySelector('.mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            fetch('mark_all_notifications_read.php', {
                method: 'POST'
            }).then(() => {
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('unread');
                    const badge = item.querySelector('.badge.bg-primary');
                    if (badge) badge.remove();
                });
                this.style.display = 'none';
            });
        });
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>