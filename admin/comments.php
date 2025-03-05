<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';
require_once '../classes/Settings.php';
require_once '../classes/Notification.php';

$auth = new Auth($conn);
$auth->requireAdmin();

$settings = Settings::getInstance($conn);
$notification = new Notification($conn);

// Xử lý các hành động
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $comment_id = $_POST['comment_id'] ?? 0;

    switch ($action) {
        case 'delete':
            $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $_SESSION['success'] = "Đã xóa bình luận thành công!";
            break;

        case 'approve':
            $stmt = $conn->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
            $stmt->execute([$comment_id]);

            // Lấy thông tin bình luận để thông báo
            $stmt = $conn->prepare("
                SELECT c.*, u.email, d.title
                FROM comments c
                JOIN users u ON c.user_id = u.id
                JOIN documents d ON c.document_id = d.id
                WHERE c.id = ?
            ");
            $stmt->execute([$comment_id]);
            $comment = $stmt->fetch();

            // Tạo thông báo cho người viết bình luận
            if ($comment) {
                $notification->create(
                    'comment_approved',
                    "Bình luận của bạn trên tài liệu '{$comment['title']}' đã được phê duyệt",
                    $comment['user_id'],
                    "view_document.php?id=" . $comment['document_id']
                );
            }

            $_SESSION['success'] = "Đã phê duyệt bình luận thành công!";
            break;

        case 'reject':
            $stmt = $conn->prepare("UPDATE comments SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$comment_id]);
            $_SESSION['success'] = "Đã từ chối bình luận thành công!";
            break;
    }

    header("Location: comments.php");
    exit();
}

// Lấy danh sách bình luận với thông tin liên quan
$stmt = $conn->prepare("
    SELECT c.*,
           u.email as user_email,
           u.full_name as user_name,
           d.title as document_title,
           d.id as document_id
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN documents d ON c.document_id = d.id
    ORDER BY c.created_at DESC
");
$stmt->execute();
$comments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý bình luận - Admin Panel</title>
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
                    <h1 class="h2">Quản lý bình luận</h1>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Người dùng</th>
                                        <th>Tài liệu</th>
                                        <th>Nội dung</th>
                                        <th>Trạng thái</th>
                                        <th>Thời gian</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comments as $comment): ?>
                                    <tr>
                                        <td><?php echo $comment['id']; ?></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($comment['user_name']); ?></div>
                                            <small class="text-muted"><?php echo $comment['user_email']; ?></small>
                                        </td>
                                        <td>
                                            <a href="../view_document.php?id=<?php echo $comment['document_id']; ?>" target="_blank">
                                                <?php echo htmlspecialchars($comment['document_title']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($comment['content']); ?></td>
                                        <td>
                                            <?php if ($settings->areCommentsModerated()): ?>
                                                <?php if ($comment['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning">Chờ duyệt</span>
                                                <?php elseif ($comment['status'] === 'approved'): ?>
                                                    <span class="badge bg-success">Đã duyệt</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Từ chối</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-success">Đã đăng</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <?php if ($settings->areCommentsModerated() && $comment['status'] === 'pending'): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                        <button type="submit" class="btn btn-success btn-sm" title="Phê duyệt">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                        <button type="submit" class="btn btn-warning btn-sm" title="Từ chối">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bình luận này?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Xóa">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>