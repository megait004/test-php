<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'documents.php' ? 'active' : ''; ?>" href="documents.php">
                    <i class="fas fa-file-alt me-2"></i>
                    Quản lý tài liệu
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="fas fa-users me-2"></i>
                    Quản lý người dùng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'comments.php' ? 'active' : ''; ?>" href="comments.php">
                    <i class="fas fa-comments me-2"></i>
                    Quản lý bình luận
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                    <i class="fas fa-bell me-2"></i>
                    Thông báo
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog me-2"></i>
                    Cài đặt hệ thống
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar me-2"></i>
                    Báo cáo thống kê
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Công cụ</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'backup.php' ? 'active' : ''; ?>" href="backup.php">
                    <i class="fas fa-database me-2"></i>
                    Sao lưu dữ liệu
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i>
                    Xem trang chủ
                </a>
            </li>
        </ul>
    </div>
</nav>