<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';

$auth = new Auth($conn);
$auth->requireAdmin();

// Lấy thống kê cơ bản
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_documents' => $conn->query("SELECT COUNT(*) FROM documents")->fetchColumn(),
    'total_comments' => $conn->query("SELECT COUNT(*) FROM comments")->fetchColumn(),
    'total_likes' => $conn->query("SELECT COUNT(*) FROM likes")->fetchColumn()
];

// Lấy thống kê người dùng theo vai trò
$user_roles = $conn->query("
    SELECT r.name as role_name, COUNT(*) as count
    FROM users u
    JOIN roles r ON u.role_id = r.id
    GROUP BY r.name
")->fetchAll();

// Lấy thống kê tài liệu theo tháng
$document_stats = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
           COUNT(*) as document_count,
           COUNT(DISTINCT user_id) as uploader_count
    FROM documents
    GROUP BY month
    ORDER BY month DESC
    LIMIT 6
")->fetchAll();

// Lấy 5 tài liệu được yêu thích nhất
$popular_documents = $conn->query("
    SELECT d.*, u.full_name as uploader_name,
           COUNT(DISTINCT l.id) as like_count,
           COUNT(DISTINCT c.id) as comment_count
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN likes l ON d.id = l.document_id
    LEFT JOIN comments c ON d.id = c.document_id
    GROUP BY d.id
    ORDER BY like_count DESC
    LIMIT 5
")->fetchAll();

// Lấy 5 người dùng tích cực nhất
$active_users = $conn->query("
    SELECT u.*,
           COUNT(DISTINCT d.id) as document_count,
           COUNT(DISTINCT c.id) as comment_count,
           COUNT(DISTINCT l.id) as like_count
    FROM users u
    LEFT JOIN documents d ON u.id = d.user_id
    LEFT JOIN comments c ON u.id = c.user_id
    LEFT JOIN likes l ON u.id = l.user_id
    GROUP BY u.id
    ORDER BY (document_count + comment_count + like_count) DESC
    LIMIT 5
")->fetchAll();

// Lấy 5 tags phổ biến nhất
$popular_tags = $conn->query("
    SELECT t.*, COUNT(dt.document_id) as usage_count
    FROM tags t
    LEFT JOIN document_tags dt ON t.id = dt.tag_id
    GROUP BY t.id
    ORDER BY usage_count DESC
    LIMIT 5
")->fetchAll();

// Lấy hoạt động gần đây
$recent_activities = $conn->query("
    (SELECT 'document' as type, d.title as content, u.full_name as user_name, d.created_at
     FROM documents d
     JOIN users u ON d.user_id = u.id)
    UNION ALL
    (SELECT 'comment' as type, c.content as content, u.full_name as user_name, c.created_at
     FROM comments c
     JOIN users u ON c.user_id = u.id)
    UNION ALL
    (SELECT 'like' as type, d.title as content, u.full_name as user_name, l.created_at
     FROM likes l
     JOIN users u ON l.user_id = u.id
     JOIN documents d ON l.document_id = d.id)
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll();

// Lấy 5 tài liệu mới nhất
$latest_documents = $conn->query("
    SELECT d.*, u.full_name as uploader_name
    FROM documents d
    LEFT JOIN users u ON d.user_id = u.id
    ORDER BY d.created_at DESC
    LIMIT 5
")->fetchAll();

// Lấy 5 người dùng mới nhất
$latest_users = $conn->query("
    SELECT u.*, r.name as role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    ORDER BY u.created_at DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-dashboard">
    <?php include_once 'includes/admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include_once 'includes/admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Xuất báo cáo</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Chia sẻ</button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                            <i class="fas fa-calendar me-1"></i>
                            Tuần này
                        </button>
                    </div>
                </div>

                <!-- Thống kê tổng quan -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Tổng người dùng</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_users']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Tổng tài liệu</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_documents']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Tổng bình luận</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_comments']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-comments fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Tổng thích</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_likes']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-heart fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Biểu đồ và thống kê -->
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Thống kê tài liệu theo tháng</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="documentChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Phân bố người dùng</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="userRoleChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Tài liệu phổ biến -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Tài liệu phổ biến</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Tiêu đề</th>
                                                <th>Người đăng</th>
                                                <th>Lượt thích</th>
                                                <th>Bình luận</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($popular_documents as $doc): ?>
                                            <tr>
                                                <td>
                                                    <a href="../view_document.php?id=<?php echo $doc['id']; ?>">
                                                        <?php echo htmlspecialchars($doc['title']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($doc['uploader_name']); ?></td>
                                                <td><?php echo $doc['like_count']; ?></td>
                                                <td><?php echo $doc['comment_count']; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Người dùng tích cực -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Người dùng tích cực</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Họ tên</th>
                                                <th>Tài liệu</th>
                                                <th>Bình luận</th>
                                                <th>Lượt thích</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($active_users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                <td><?php echo $user['document_count']; ?></td>
                                                <td><?php echo $user['comment_count']; ?></td>
                                                <td><?php echo $user['like_count']; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Tags phổ biến -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Tags phổ biến</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($popular_tags as $tag): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($tag['name']); ?></span>
                                    <span class="text-muted"><?php echo $tag['usage_count']; ?> tài liệu</span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Hoạt động gần đây -->
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Hoạt động gần đây</h5>
                            </div>
                            <div class="card-body">
                                <div class="activity-feed">
                                    <?php foreach ($recent_activities as $activity): ?>
                                    <div class="activity-item d-flex align-items-center mb-3">
                                        <div class="activity-icon me-3">
                                            <?php if ($activity['type'] === 'document'): ?>
                                                <i class="fas fa-file-alt text-primary"></i>
                                            <?php elseif ($activity['type'] === 'comment'): ?>
                                                <i class="fas fa-comment text-success"></i>
                                            <?php else: ?>
                                                <i class="fas fa-heart text-danger"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-content">
                                            <strong><?php echo htmlspecialchars($activity['user_name']); ?></strong>
                                            <?php
                                            switch ($activity['type']) {
                                                case 'document':
                                                    echo ' đã tải lên tài liệu ';
                                                    break;
                                                case 'comment':
                                                    echo ' đã bình luận ';
                                                    break;
                                                case 'like':
                                                    echo ' đã thích tài liệu ';
                                                    break;
                                            }
                                            ?>
                                            <strong><?php echo htmlspecialchars($activity['content']); ?></strong>
                                            <small class="text-muted d-block">
                                                <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danh sách tài liệu mới -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-file-alt me-1"></i>
                        Tài liệu mới nhất
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Tiêu đề</th>
                                        <th>Người đăng</th>
                                        <th>Ngày đăng</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latest_documents as $doc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($doc['title']); ?></td>
                                        <td><?php echo htmlspecialchars($doc['uploader_name']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($doc['created_at'])); ?></td>
                                        <td>
                                            <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
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
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest"></script>
    <script>
    // Biểu đồ tài liệu theo tháng
    const documentCtx = document.getElementById('documentChart').getContext('2d');
    new Chart(documentCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column(array_reverse($document_stats), 'month')); ?>,
            datasets: [{
                label: 'Số lượng tài liệu',
                data: <?php echo json_encode(array_column(array_reverse($document_stats), 'document_count')); ?>,
                borderColor: '#0061f2',
                tension: 0.3,
                fill: false
            }, {
                label: 'Số người đăng',
                data: <?php echo json_encode(array_column(array_reverse($document_stats), 'uploader_count')); ?>,
                borderColor: '#00ba94',
                tension: 0.3,
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });

    // Biểu đồ phân bố người dùng
    const userRoleCtx = document.getElementById('userRoleChart').getContext('2d');
    new Chart(userRoleCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($user_roles, 'role_name')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($user_roles, 'count')); ?>,
                backgroundColor: ['#0061f2', '#00ba94', '#f4a100'],
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
    </script>
</body>
</html>