<?php
session_start();
require_once 'config/database.php';

// Lấy danh sách tài liệu mới nhất
$latest_docs = $conn->query("
    SELECT d.*, u.full_name as uploader_name,
           COUNT(DISTINCT l.id) as like_count,
           COUNT(DISTINCT c.id) as comment_count
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN likes l ON d.id = l.document_id
    LEFT JOIN comments c ON d.id = c.document_id
    GROUP BY d.id
    ORDER BY d.created_at DESC
    LIMIT 6
")->fetchAll();

// Lấy tài liệu phổ biến nhất
$popular_docs = $conn->query("
    SELECT d.*, u.full_name as uploader_name,
           COUNT(DISTINCT l.id) as like_count,
           COUNT(DISTINCT c.id) as comment_count
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN likes l ON d.id = l.document_id
    LEFT JOIN comments c ON d.id = c.document_id
    GROUP BY d.id
    ORDER BY (like_count + comment_count) DESC
    LIMIT 6
")->fetchAll();

// Lấy tất cả tags để làm menu
$tags = $conn->query("
    SELECT t.*, COUNT(dt.document_id) as doc_count
    FROM tags t
    LEFT JOIN document_tags dt ON t.id = dt.tag_id
    GROUP BY t.id
    HAVING doc_count > 0
    ORDER BY doc_count DESC
")->fetchAll();

// Hàm lấy tags cho mỗi tài liệu
function getDocumentTags($conn, $document_id) {
    $stmt = $conn->prepare("
        SELECT t.name, t.id
        FROM tags t
        JOIN document_tags dt ON t.id = dt.tag_id
        WHERE dt.document_id = ?
    ");
    $stmt->execute([$document_id]);
    return $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #0061f2 0%, #00ba94 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 2rem;
        }
        .card {
            transition: transform 0.2s;
            margin-bottom: 1rem;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .tag-badge {
            text-decoration: none;
            transition: all 0.2s;
        }
        .tag-badge:hover {
            transform: scale(1.05);
        }
        .section-title {
            position: relative;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: #0061f2;
        }
        .stats-box {
            text-align: center;
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stats-box i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #0061f2;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-4 fw-bold mb-4">Khám phá kho tài liệu</h1>
                    <p class="lead mb-4">Truy cập hàng nghìn tài liệu chất lượng cao, chia sẻ kiến thức và học hỏi từ cộng đồng.</p>
                    <div class="d-flex gap-3">
                        <a href="search.php" class="btn btn-light btn-lg">
                            <i class="fas fa-search me-2"></i>Tìm kiếm
                        </a>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <a href="login.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-user me-2"></i>Đăng nhập
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 d-none d-md-block">
                    <img src="assets/images/hero.png" alt="Hero Image" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Tags Section -->
        <section class="mb-5">
            <h2 class="section-title">Danh mục tài liệu</h2>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($tags as $tag): ?>
                    <a href="documents.php?tag=<?php echo $tag['id']; ?>"
                       class="tag-badge badge bg-primary bg-opacity-75 p-2">
                        <?php echo htmlspecialchars($tag['name']); ?>
                        <span class="badge bg-light text-primary ms-1">
                            <?php echo $tag['doc_count']; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Latest Documents -->
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title mb-0">Tài liệu mới nhất</h2>
                <a href="documents.php?sort=latest" class="btn btn-outline-primary">
                    Xem tất cả <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
            <div class="row">
                <?php foreach ($latest_docs as $doc): ?>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="view_document.php?id=<?php echo $doc['id']; ?>"
                                       class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($doc['title']); ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted">
                                    <?php echo substr(htmlspecialchars($doc['content']), 0, 100); ?>...
                                </p>
                                <div class="mb-2">
                                    <?php foreach (getDocumentTags($conn, $doc['id']) as $tag): ?>
                                        <a href="documents.php?tag=<?php echo $tag['id']; ?>"
                                           class="badge bg-secondary text-decoration-none me-1">
                                            <?php echo htmlspecialchars($tag['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        <?php echo htmlspecialchars($doc['uploader_name']); ?>
                                    </small>
                                    <div>
                                        <small class="text-muted me-2">
                                            <i class="fas fa-heart text-danger"></i>
                                            <?php echo $doc['like_count']; ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-comment text-primary"></i>
                                            <?php echo $doc['comment_count']; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Popular Documents -->
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="section-title mb-0">Tài liệu phổ biến</h2>
                <a href="documents.php?sort=popular" class="btn btn-outline-primary">
                    Xem tất cả <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
            <div class="row">
                <?php foreach ($popular_docs as $doc): ?>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="view_document.php?id=<?php echo $doc['id']; ?>"
                                       class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($doc['title']); ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted">
                                    <?php echo substr(htmlspecialchars($doc['content']), 0, 100); ?>...
                                </p>
                                <div class="mb-2">
                                    <?php foreach (getDocumentTags($conn, $doc['id']) as $tag): ?>
                                        <a href="documents.php?tag=<?php echo $tag['id']; ?>"
                                           class="badge bg-secondary text-decoration-none me-1">
                                            <?php echo htmlspecialchars($tag['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        <?php echo htmlspecialchars($doc['uploader_name']); ?>
                                    </small>
                                    <div>
                                        <small class="text-muted me-2">
                                            <i class="fas fa-heart text-danger"></i>
                                            <?php echo $doc['like_count']; ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-comment text-primary"></i>
                                            <?php echo $doc['comment_count']; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>