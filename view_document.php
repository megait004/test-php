<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';
require_once 'classes/Settings.php';
require_once 'classes/Notification.php';
require_once 'includes/header.php';

$settings = Settings::getInstance($conn);
$notification = new Notification($conn);
$is_logged_in = isset($_SESSION['user_id']);

// Lấy ID tài liệu
$document_id = $_GET['id'] ?? 0;

// Lấy thông tin tài liệu
$stmt = $conn->prepare("
    SELECT d.*, u.full_name as uploader_name, u.id as uploader_id
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    WHERE d.id = ?
");
$stmt->execute([$document_id]);
$document = $stmt->fetch();

if (!$document) {
    $_SESSION['error'] = "Không tìm thấy tài liệu!";
    header("Location: index.php");
    exit();
}

// Lấy tags của tài liệu
$stmt = $conn->prepare("
    SELECT t.name
    FROM tags t
    JOIN document_tags dt ON t.id = dt.tag_id
    WHERE dt.document_id = ?
");
$stmt->execute([$document_id]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Xử lý thêm bình luận
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in && $settings->areCommentsAllowed()) {
    $content = $_POST['content'] ?? '';

    if (!empty($content)) {
        // Kiểm tra spam
        $is_spam = $settings->isCommentSpam($content);

        // Xác định trạng thái bình luận
        $status = 'approved';
        if ($settings->areCommentsModerated() || $is_spam) {
            $status = 'pending';
        }

        $stmt = $conn->prepare("
            INSERT INTO comments (document_id, user_id, content, status)
            VALUES (?, ?, ?, ?)
        ");

        if ($stmt->execute([$document_id, $_SESSION['user_id'], $content, $status])) {
            // Tạo thông báo cho admin về bình luận mới cần duyệt
            if ($status === 'pending') {
                // Thông báo cho admin
                $notification->create(
                    'new_comment',
                    "Bình luận mới cần duyệt từ " . $_SESSION['user_name'],
                    null, // null để tất cả admin đều nhận được
                    "admin/comments.php?document_id=" . $document_id
                );
                $_SESSION['success'] = "Bình luận của bạn đang chờ kiểm duyệt";
            } else {
                // Thông báo cho người đăng tài liệu
                if ($document['uploader_id'] != $_SESSION['user_id']) {
                    $notification->create(
                        'document_comment',
                        $_SESSION['user_name'] . " đã bình luận về tài liệu của bạn",
                        $document['uploader_id'],
                        "view_document.php?id=" . $document_id
                    );
                }
                $_SESSION['success'] = "Đã thêm bình luận thành công";
            }
        } else {
            $_SESSION['error'] = "Có lỗi xảy ra khi thêm bình luận";
        }

        header("Location: view_document.php?id=" . $document_id);
        exit();
    }
}

// Lấy danh sách bình luận
$comments_sql = "
    SELECT c.*, u.full_name, u.email
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.document_id = ? AND c.status = 'approved'
    ORDER BY c.created_at DESC
";
$stmt = $conn->prepare($comments_sql);
$stmt->execute([$document_id]);
$comments = $stmt->fetchAll();

// Xử lý like/unlike
if ($is_logged_in && isset($_POST['action'])) {
    if ($_POST['action'] === 'like') {
        $stmt = $conn->prepare("
            INSERT INTO likes (document_id, user_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
        ");
        if ($stmt->execute([$document_id, $_SESSION['user_id']])) {
            // Thông báo cho người đăng tài liệu
            if ($document['uploader_id'] != $_SESSION['user_id']) {
                $notification->create(
                    'document_like',
                    $_SESSION['user_name'] . " đã thích tài liệu của bạn",
                    $document['uploader_id'],
                    "view_document.php?id=" . $document_id
                );
            }
        }
    } elseif ($_POST['action'] === 'unlike') {
        $stmt = $conn->prepare("DELETE FROM likes WHERE document_id = ? AND user_id = ?");
        $stmt->execute([$document_id, $_SESSION['user_id']]);
    }
    exit();
}

// Kiểm tra user đã like chưa
$has_liked = false;
if ($is_logged_in) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE document_id = ? AND user_id = ?");
    $stmt->execute([$document_id, $_SESSION['user_id']]);
    $has_liked = $stmt->fetchColumn() > 0;
}

// Lấy số lượt like
$stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE document_id = ?");
$stmt->execute([$document_id]);
$like_count = $stmt->fetchColumn();
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h1 class="card-title"><?php echo htmlspecialchars($document['title']); ?></h1>
                    <p class="text-muted">
                        Đăng bởi <?php echo htmlspecialchars($document['uploader_name']); ?>
                        - <?php echo date('d/m/Y H:i', strtotime($document['created_at'])); ?>
                    </p>
                    <div class="document-content mb-4">
                        <?php echo nl2br(htmlspecialchars($document['content'])); ?>
                    </div>
                    <?php if ($document['download_link']): ?>
                        <a href="download.php?id=<?php echo $document['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Tải xuống
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Phần bình luận -->
            <div class="card mt-4">
                <div class="card-body">
                    <h4 class="card-title">Bình luận</h4>
                    <?php if ($settings->areCommentsAllowed()): ?>
                        <?php if ($is_logged_in): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <textarea class="form-control" name="content" rows="3" required
                                              placeholder="Viết bình luận của bạn..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Gửi bình luận
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Vui lòng <a href="login.php">đăng nhập</a> để bình luận
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Tính năng bình luận đang bị vô hiệu hóa
                        </div>
                    <?php endif; ?>

                    <div class="comments-list mt-4">
                        <?php if (empty($comments)): ?>
                            <div class="text-muted">Chưa có bình luận nào</div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item mb-3 pb-3 border-bottom">
                                    <div class="d-flex align-items-start">
                                        <img src="https://www.gravatar.com/avatar/<?php echo md5(strtolower($comment['email'])); ?>?s=40&d=mp"
                                             class="rounded-circle me-2" width="40" height="40" alt="Avatar">
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($comment['full_name']); ?></div>
                                            <div class="text-muted small">
                                                <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                                            </div>
                                            <div class="mt-2">
                                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Thông tin tài liệu</h5>
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <button class="btn <?php echo $has_liked ? 'btn-danger' : 'btn-outline-danger'; ?>"
                                    onclick="toggleLike(this, <?php echo $document['id']; ?>)"
                                    <?php echo !$is_logged_in ? 'disabled' : ''; ?>>
                                <i class="fas fa-heart"></i>
                                <span class="like-count"><?php echo $like_count; ?></span>
                            </button>
                        </div>
                        <div>
                            <i class="fas fa-comment text-primary"></i>
                            <span class="ms-1"><?php echo count($comments); ?> bình luận</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleLike(button, documentId) {
    const action = button.classList.contains('btn-danger') ? 'unlike' : 'like';

    fetch('view_document.php?id=' + documentId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=' + action
    }).then(() => {
        const likeCount = button.querySelector('.like-count');
        const currentCount = parseInt(likeCount.textContent);

        if (action === 'like') {
            button.classList.remove('btn-outline-danger');
            button.classList.add('btn-danger');
            likeCount.textContent = currentCount + 1;
        } else {
            button.classList.remove('btn-danger');
            button.classList.add('btn-outline-danger');
            likeCount.textContent = currentCount - 1;
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>