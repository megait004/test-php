<?php
session_start();
require_once 'config/database.php';

$search = $_GET['q'] ?? '';
$tag_id = $_GET['tag'] ?? 0;
$sort = $_GET['sort'] ?? 'latest';

// Xây dựng câu truy vấn cơ bản
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
";

// Thêm điều kiện tìm kiếm
$params = [];
$conditions = [];

if (!empty($search)) {
    $conditions[] = "(d.title LIKE ? OR d.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($tag_id) {
    $conditions[] = "dt.tag_id = ?";
    $params[] = $tag_id;
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " GROUP BY d.id";

// Thêm sắp xếp
switch ($sort) {
    case 'popular':
        $query .= " ORDER BY (like_count + comment_count) DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY d.created_at ASC";
        break;
    default:
        $query .= " ORDER BY d.created_at DESC";
}

// Thực hiện truy vấn
$stmt = $conn->prepare($query);
$stmt->execute($params);
$documents = $stmt->fetchAll();

// Lấy tất cả tags cho filter
$tags = $conn->query("
    SELECT t.*, COUNT(dt.document_id) as doc_count
    FROM tags t
    LEFT JOIN document_tags dt ON t.id = dt.tag_id
    GROUP BY t.id
    HAVING doc_count > 0
    ORDER BY doc_count DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm kiếm tài liệu - Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row">
            <!-- Sidebar lọc -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Lọc theo danh mục</h5>
                        <div class="list-group mt-3">
                            <a href="search.php?q=<?php echo urlencode($search); ?>"
                               class="list-group-item list-group-item-action <?php echo !$tag_id ? 'active' : ''; ?>">
                                Tất cả
                            </a>
                            <?php foreach ($tags as $tag): ?>
                                <a href="search.php?q=<?php echo urlencode($search); ?>&tag=<?php echo $tag['id']; ?>"
                                   class="list-group-item list-group-item-action <?php echo $tag_id == $tag['id'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                    <span class="badge bg-secondary float-end"><?php echo $tag['doc_count']; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kết quả tìm kiếm -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <form action="search.php" method="GET" class="mb-4">
                            <div class="input-group">
                                <input type="text" class="form-control" name="q"
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       placeholder="Nhập từ khóa tìm kiếm...">
                                <?php if ($tag_id): ?>
                                    <input type="hidden" name="tag" value="<?php echo $tag_id; ?>">
                                <?php endif; ?>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i> Tìm kiếm
                                </button>
                            </div>
                        </form>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">
                                <?php if (!empty($search)): ?>
                                    Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search); ?>"
                                <?php else: ?>
                                    Tất cả tài liệu
                                <?php endif; ?>
                            </h5>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown">
                                    <?php
                                    switch ($sort) {
                                        case 'popular':
                                            echo 'Phổ biến nhất';
                                            break;
                                        case 'oldest':
                                            echo 'Cũ nhất';
                                            break;
                                        default:
                                            echo 'Mới nhất';
                                    }
                                    ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item <?php echo $sort === 'latest' ? 'active' : ''; ?>"
                                           href="?q=<?php echo urlencode($search); ?><?php echo $tag_id ? "&tag=$tag_id" : ''; ?>&sort=latest">
                                            Mới nhất
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item <?php echo $sort === 'oldest' ? 'active' : ''; ?>"
                                           href="?q=<?php echo urlencode($search); ?><?php echo $tag_id ? "&tag=$tag_id" : ''; ?>&sort=oldest">
                                            Cũ nhất
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item <?php echo $sort === 'popular' ? 'active' : ''; ?>"
                                           href="?q=<?php echo urlencode($search); ?><?php echo $tag_id ? "&tag=$tag_id" : ''; ?>&sort=popular">
                                            Phổ biến nhất
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <?php if (empty($documents)): ?>
                            <div class="alert alert-info">
                                Không tìm thấy tài liệu nào phù hợp.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($documents as $doc): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    <a href="view_document.php?id=<?php echo $doc['id']; ?>"
                                                       class="text-decoration-none text-dark">
                                                        <?php echo htmlspecialchars($doc['title']); ?>
                                                    </a>
                                                </h5>
                                                <p class="card-text text-muted">
                                                    <?php echo substr(htmlspecialchars($doc['content']), 0, 150); ?>...
                                                </p>
                                                <?php if ($doc['tags']): ?>
                                                    <div class="mb-2">
                                                        <?php foreach (explode(',', $doc['tags']) as $tag): ?>
                                                            <span class="badge bg-secondary me-1">
                                                                <?php echo htmlspecialchars($tag); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>