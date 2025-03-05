<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Xử lý upload file
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $tags = $_POST['tags'] ?? '';

    $errors = [];

    // Validate dữ liệu
    if (empty($title)) {
        $errors[] = "Tiêu đề không được để trống";
    }
    if (empty($content)) {
        $errors[] = "Nội dung không được để trống";
    }

    // Xử lý upload file
    $download_link = null;
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = uniqid() . '_' . $_FILES['document']['name'];
        $upload_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['document']['tmp_name'], $upload_path)) {
            $download_link = $upload_path;
        } else {
            $errors[] = "Không thể tải lên file";
        }
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Thêm document
            $stmt = $conn->prepare("INSERT INTO documents (title, content, download_link, user_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $content, $download_link, $_SESSION['user_id']]);
            $document_id = $conn->lastInsertId();

            // Xử lý tags
            if (!empty($tags)) {
                $tag_names = array_map('trim', explode(',', $tags));
                foreach ($tag_names as $tag_name) {
                    if (empty($tag_name)) continue;

                    // Kiểm tra tag đã tồn tại chưa
                    $stmt = $conn->prepare("SELECT id FROM tags WHERE name = ?");
                    $stmt->execute([$tag_name]);
                    $tag = $stmt->fetch();

                    if (!$tag) {
                        // Tạo tag mới
                        $stmt = $conn->prepare("INSERT INTO tags (name) VALUES (?)");
                        $stmt->execute([$tag_name]);
                        $tag_id = $conn->lastInsertId();
                    } else {
                        $tag_id = $tag['id'];
                    }

                    // Liên kết tag với document
                    $stmt = $conn->prepare("INSERT INTO document_tags (document_id, tag_id) VALUES (?, ?)");
                    $stmt->execute([$document_id, $tag_id]);
                }
            }

            $conn->commit();
            $_SESSION['success'] = "Tải lên tài liệu thành công!";
            header("Location: index.php");
            exit();

        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "Có lỗi xảy ra: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tải lên tài liệu - Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Quản lý tài liệu</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="upload.php">Tải lên tài liệu</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <span class="nav-item nav-link text-light">Xin chào, <?php echo $_SESSION['user_name']; ?></span>
                    <a class="nav-link" href="logout.php">Đăng xuất</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Tải lên tài liệu mới</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card mt-4">
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Tiêu đề</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>

                    <div class="mb-3">
                        <label for="content" class="form-label">Nội dung</label>
                        <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="document" class="form-label">File tài liệu (không bắt buộc)</label>
                        <input type="file" class="form-control" id="document" name="document">
                    </div>

                    <div class="mb-3">
                        <label for="tags" class="form-label">Tags (phân cách bằng dấu phẩy)</label>
                        <input type="text" class="form-control" id="tags" name="tags" placeholder="php, web, programming">
                    </div>

                    <button type="submit" class="btn btn-primary">Tải lên</button>
                    <a href="index.php" class="btn btn-secondary">Hủy</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>