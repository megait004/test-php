<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';

$auth = new Auth($conn);
$auth->requireAdmin();

// Xử lý xóa tài liệu
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $document_id = $_POST['document_id'] ?? 0;
    try {
        // Lấy thông tin file để xóa
        $stmt = $conn->prepare("SELECT download_link FROM documents WHERE id = ?");
        $stmt->execute([$document_id]);
        $document = $stmt->fetch();

        // Xóa file nếu tồn tại
        if ($document && $document['download_link'] && file_exists($document['download_link'])) {
            unlink('../' . $document['download_link']);
        }

        // Xóa record trong database
        $stmt = $conn->prepare("DELETE FROM documents WHERE id = ?");
        if ($stmt->execute([$document_id])) {
            $_SESSION['success'] = "Đã xóa tài liệu thành công!";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
    }
    header("Location: documents.php");
    exit();
}

// Lấy danh sách tài liệu
$query = "
    SELECT d.*, u.full_name as uploader_name,
           COUNT(DISTINCT l.id) as like_count,
           COUNT(DISTINCT c.id) as comment_count,
           GROUP_CONCAT(DISTINCT t.name) as tags
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN likes l ON d.id = l.document_id
    LEFT JOIN comments c ON d.id = c.document_id
    LEFT JOIN document_tags dt ON d.id = dt.document_id
    LEFT JOIN tags t ON dt.tag_id = t.id
    GROUP BY d.id
    ORDER BY d.created_at DESC
";

$documents = $conn->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài liệu - Admin Panel</title>
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
                    <h1 class="h2">Quản lý tài liệu</h1>
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

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tiêu đề</th>
                                <th>Người đăng</th>
                                <th>Tags</th>
                                <th>Lượt thích</th>
                                <th>Bình luận</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td><?php echo $doc['id']; ?></td>
                                    <td>
                                        <a href="../view_document.php?id=<?php echo $doc['id']; ?>">
                                            <?php echo htmlspecialchars($doc['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($doc['uploader_name']); ?></td>
                                    <td>
                                        <?php if ($doc['tags']): ?>
                                            <?php foreach (explode(',', $doc['tags']) as $tag): ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($tag); ?></span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $doc['like_count']; ?></td>
                                    <td><?php echo $doc['comment_count']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($doc['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="../view_document.php?id=<?php echo $doc['id']; ?>"
                                               class="btn btn-sm btn-primary" title="Xem">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($doc['download_link']): ?>
                                                <a href="../<?php echo $doc['download_link']; ?>"
                                                   class="btn btn-sm btn-success" title="Tải xuống">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php endif; ?>
                                            <form method="POST" action="" class="d-inline"
                                                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa tài liệu này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Xóa">
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
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>