<?php
session_start();
require_once '../config/database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Xử lý thêm tag mới
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $tag_name = trim($_POST['tag_name'] ?? '');
    if (!empty($tag_name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO tags (name) VALUES (?)");
            $stmt->execute([$tag_name]);
            $_SESSION['success'] = "Đã thêm tag mới thành công!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $_SESSION['error'] = "Tag này đã tồn tại!";
            } else {
                $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
            }
        }
    }
    header("Location: tags.php");
    exit();
}

// Xử lý xóa tag
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $tag_id = $_POST['tag_id'] ?? 0;
    try {
        $stmt = $conn->prepare("DELETE FROM tags WHERE id = ?");
        $stmt->execute([$tag_id]);
        $_SESSION['success'] = "Đã xóa tag thành công!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
    }
    header("Location: tags.php");
    exit();
}

// Xử lý sửa tag
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $tag_id = $_POST['tag_id'] ?? 0;
    $new_name = trim($_POST['new_name'] ?? '');
    if (!empty($new_name)) {
        try {
            $stmt = $conn->prepare("UPDATE tags SET name = ? WHERE id = ?");
            $stmt->execute([$new_name, $tag_id]);
            $_SESSION['success'] = "Đã cập nhật tag thành công!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $_SESSION['error'] = "Tag này đã tồn tại!";
            } else {
                $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
            }
        }
    }
    header("Location: tags.php");
    exit();
}

// Lấy danh sách tags với số lượng tài liệu
$tags = $conn->query("
    SELECT t.*, COUNT(DISTINCT dt.document_id) as document_count
    FROM tags t
    LEFT JOIN document_tags dt ON t.id = dt.tag_id
    GROUP BY t.id
    ORDER BY t.name ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Tags - Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-book-reader me-2"></i>Quản lý tài liệu
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Quản lý người dùng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="documents.php">Quản lý tài liệu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="tags.php">Quản lý tags</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <span class="nav-item nav-link text-light">
                        <i class="fas fa-user me-2"></i><?php echo $_SESSION['user_name']; ?>
                    </span>
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-2 d-none d-md-block admin-sidebar">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-home"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i>
                                Quản lý người dùng
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="documents.php">
                                <i class="fas fa-file-alt"></i>
                                Quản lý tài liệu
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="tags.php">
                                <i class="fas fa-tags"></i>
                                Quản lý tags
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-10 ms-sm-auto px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2">Quản lý Tags</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTagModal">
                        <i class="fas fa-plus me-2"></i>Thêm tag mới
                    </button>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên tag</th>
                                        <th>Số tài liệu</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tags as $tag): ?>
                                    <tr>
                                        <td><?php echo $tag['id']; ?></td>
                                        <td><?php echo htmlspecialchars($tag['name']); ?></td>
                                        <td><?php echo $tag['document_count']; ?></td>
                                        <td>
                                            <button type="button" class="btn btn-warning btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editTagModal<?php echo $tag['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" action="" class="d-inline"
                                                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa tag này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Modal Edit Tag -->
                                    <div class="modal fade" id="editTagModal<?php echo $tag['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Sửa tag</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                                                        <div class="mb-3">
                                                            <label for="new_name" class="form-label">Tên tag mới</label>
                                                            <input type="text" class="form-control" id="new_name" name="new_name"
                                                                   value="<?php echo htmlspecialchars($tag['name']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Add Tag -->
    <div class="modal fade" id="addTagModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm tag mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="tag_name" class="form-label">Tên tag</label>
                            <input type="text" class="form-control" id="tag_name" name="tag_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Thêm mới</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>