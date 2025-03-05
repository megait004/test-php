<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';

$auth = new Auth($conn);
$auth->requireAdmin();

// Lấy thống kê theo thời gian
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Thống kê tài liệu theo ngày
$document_stats = $conn->prepare("
    SELECT DATE(created_at) as date,
           COUNT(*) as count
    FROM documents
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date
");
$document_stats->execute([$start_date, $end_date]);
$documents_by_date = $document_stats->fetchAll();

// Thống kê người dùng mới
$new_users = $conn->prepare("
    SELECT DATE(created_at) as date,
           COUNT(*) as count
    FROM users
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date
");
$new_users->execute([$start_date, $end_date]);
$users_by_date = $new_users->fetchAll();

// Top người dùng tích cực
$active_users = $conn->query("
    SELECT u.full_name,
           COUNT(DISTINCT d.id) as document_count,
           COUNT(DISTINCT c.id) as comment_count,
           COUNT(DISTINCT l.id) as like_count
    FROM users u
    LEFT JOIN documents d ON u.id = d.user_id
    LEFT JOIN comments c ON u.id = c.user_id
    LEFT JOIN likes l ON u.id = l.user_id
    GROUP BY u.id
    ORDER BY (document_count + comment_count + like_count) DESC
    LIMIT 10
")->fetchAll();

// Top tài liệu phổ biến
$popular_docs = $conn->query("
    SELECT d.title,
           u.full_name as author,
           COUNT(DISTINCT l.id) as likes,
           COUNT(DISTINCT c.id) as comments
    FROM documents d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN likes l ON d.id = l.document_id
    LEFT JOIN comments c ON d.id = c.document_id
    GROUP BY d.id
    ORDER BY (likes + comments) DESC
    LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo thống kê - Hệ thống quản lý tài liệu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2">Báo cáo thống kê</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <form class="d-flex gap-2">
                            <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                            <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                            <button type="submit" class="btn btn-primary">Lọc</button>
                        </form>
                    </div>
                </div>

                <!-- Biểu đồ thống kê -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Tài liệu mới</h5>
                                <canvas id="documentsChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Người dùng mới</h5>
                                <canvas id="usersChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bảng thống kê -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Top người dùng tích cực</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Người dùng</th>
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

                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Top tài liệu phổ biến</h5>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Tài liệu</th>
                                                <th>Tác giả</th>
                                                <th>Lượt thích</th>
                                                <th>Bình luận</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($popular_docs as $doc): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($doc['title']); ?></td>
                                                <td><?php echo htmlspecialchars($doc['author']); ?></td>
                                                <td><?php echo $doc['likes']; ?></td>
                                                <td><?php echo $doc['comments']; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Khởi tạo biểu đồ
    const documentsChart = new Chart(
        document.getElementById('documentsChart'),
        {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($documents_by_date, 'date')); ?>,
                datasets: [{
                    label: 'Số tài liệu mới',
                    data: <?php echo json_encode(array_column($documents_by_date, 'count')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            }
        }
    );

    const usersChart = new Chart(
        document.getElementById('usersChart'),
        {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($users_by_date, 'date')); ?>,
                datasets: [{
                    label: 'Người dùng mới',
                    data: <?php echo json_encode(array_column($users_by_date, 'count')); ?>,
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }]
            }
        }
    );
    </script>
</body>
</html>