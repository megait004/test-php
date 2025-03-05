<?php
session_start();
require_once 'config/database.php';

// Lấy các tham số lọc và sắp xếp
$tag_id = $_GET['tag'] ?? 0;
$sort = $_GET['sort'] ?? 'latest';
$page = $_GET['page'] ?? 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Xây dựng câu truy vấn cơ bản
$query = "
    SELECT d.*, u.full_name as uploader_name,
           COUNT(DISTINCT l.id) as like_count,
           COUNT(DISTINCT c.id) as comment_count
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN likes l ON d.id = l.document_id
    LEFT JOIN comments c ON d.id = c.document_id
";

// Thêm điều kiện lọc theo tag nếu có
if ($tag_id) {
    $query .= "
        JOIN document_tags dt ON d.id = dt.document_id
        WHERE dt.tag_id = " . intval($tag_id) . "
    ";
}

$query .= " GROUP BY d.id ";

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

// Thêm phân trang
$query .= " LIMIT $per_page OFFSET $offset";

// Lấy danh sách tài liệu
$documents = $conn->query($query)->fetchAll();

// Đếm tổng số tài liệu để phân trang
$count_query = "SELECT COUNT(DISTINCT d.id) as total FROM documents d";
if ($tag_id) {
    $count_query .= " JOIN document_tags dt ON d.id = dt.document_id WHERE dt.tag_id = " . intval($tag_id);
}
$total_documents = $conn->query($count_query)->fetch()['total'];
$total_pages = ceil($total_documents / $per_page);

// Lấy thông tin tag nếu đang lọc theo tag
$current_tag = null;
if ($tag_id) {
    $stmt = $conn->prepare("SELECT * FROM tags WHERE id = ?");
    $stmt->execute([$tag_id]);
    $current_tag = $stmt->fetch();
}

// Lấy tất cả tags để làm filter
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
    <title><?php echo $current_tag ? htmlspecialchars($current_tag['name']) : 'Tất cả tài liệu'; ?> - Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .filter-sidebar {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
        }
        .sort-dropdown .dropdown-item.active {
            background-color: #e9ecef;
            color: #000;
        }
        .document-card {
            height: 100%;
            transition: transform 0.2s;
        }
        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-4">
        <div class="row">
            <!-- Sidebar lọc -->
            <div class="col-md-3">
                <div class="filter-sidebar">
                    <h5 class="mb-3">Danh mục</h5>
                    <div class="list-group">
                        <a href="documents.php"
                           class="list-group-item list-group-item-action <?php echo !$tag_id ? 'active' : ''; ?>">
                            Tất cả tài liệu
                        </a>
                        <?php foreach ($tags as $tag): ?>
                            <a href="?tag=<?php echo $tag['id']; ?>"
                               class="list-group-item list-group-item-action <?php echo $tag_id == $tag['id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($tag['name']); ?>
                                <span class="badge bg-secondary float-end"><?php echo $tag['doc_count']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Danh sách tài liệu -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <?php if ($current_tag): ?>
                            Tài liệu về "<?php echo htmlspecialchars($current_tag['name']); ?>"
                        <?php else: ?>
                            Tất cả tài liệu
                        <?php endif; ?>
                    </h2>
                    <div class="dropdown sort-dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
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
                                   href="?<?php echo $tag_id ? "tag=$tag_id&" : ''; ?>sort=latest">
                                    Mới nhất
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo $sort === 'oldest' ? 'active' : ''; ?>"
                                   href="?<?php echo $tag_id ? "tag=$tag_id&" : ''; ?>sort=oldest">
                                    Cũ nhất
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo $sort === 'popular' ? 'active' : ''; ?>"
                                   href="?<?php echo $tag_id ? "tag=$tag_id&" : ''; ?>sort=popular">
                                    Phổ biến nhất
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <?php if (empty($documents)): ?>
                    <div class="alert alert-info">
                        Không tìm thấy tài liệu nào.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($documents as $doc): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card document-card">
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
                                                <a href="?tag=<?php echo $tag['id']; ?>"
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

                    <!-- Phân trang -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo $tag_id ? "tag=$tag_id&" : ''; ?>page=<?php echo $page - 1; ?>&sort=<?php echo $sort; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo $tag_id ? "tag=$tag_id&" : ''; ?>page=<?php echo $i; ?>&sort=<?php echo $sort; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo $tag_id ? "tag=$tag_id&" : ''; ?>page=<?php echo $page + 1; ?>&sort=<?php echo $sort; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>