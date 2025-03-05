<footer class="footer bg-dark text-white">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5>Về chúng tôi</h5>
                <p>Hệ thống quản lý tài liệu trực tuyến, nơi chia sẻ và lưu trữ tài liệu an toàn, tiện lợi.</p>
                <div class="social-links mt-3">
                    <a href="https://www.facebook.com/giapzech" class="me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://x.com/AnimeCute41004" class="me-3"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.instagram.com/" class="me-3"><i class="fab fa-linkedin-in"></i></a>
                    <a href="https://github.com/megait004" class="me-3"><i class="fab fa-github"></i></a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Liên kết nhanh</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="about.php">Giới thiệu</a></li>
                    <li><a href="contact.php">Liên hệ</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="upload.php">Tải lên tài liệu</a></li>
                        <li><a href="my_documents.php">Tài liệu của tôi</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Đăng nhập</a></li>
                        <li><a href="register.php">Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Thông tin liên hệ</h5>
                <ul class="list-unstyled">
                    <li>
                        <i class="fas fa-map-marker-alt me-2"></i>
                        Ngõ 42 , Trần Bình , Hà Nội
                    </li>
                    <li>
                        <i class="fas fa-phone me-2"></i>
                        (84) 0528286001
                    </li>
                    <li>
                        <i class="fas fa-envelope me-2"></i>
                        Nguyễn Nguyên Giáp
                    </li>
                </ul>
            </div>
        </div>
        <hr class="mt-4 mb-4">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Hệ thống quản lý tài liệu.Copyright © Giapzech 2025.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-0">
                    <a href="#">Điều khoản sử dụng</a>
                    <span class="mx-2">|</span>
                    <a href="#">Chính sách bảo mật</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Font Awesome -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<!-- Custom JS -->
<script>
// Thêm class active cho nav-link hiện tại
document.addEventListener('DOMContentLoaded', function() {
    const currentLocation = location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentLocation) {
            link.classList.add('active');
        }
    });
});
</script>