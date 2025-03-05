<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireAdmin();

// Lấy các tham số lọc
$user_id = $_GET['user_id'] ?? '';
$action_type = $_GET['action_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Xây dựng câu truy vấn
$query = "
    SELECT al.*, u.full_name as user_name
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($user_id) {
    $query .= " AND al.user_id = ?";
    $params[] = $user_id;
}

if ($action_type) {
    $query .= " AND al.action_type = ?";
    $params[] = $action_type;
}

if ($date_from) {
    $query .= " AND DATE(al.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(al.created_at) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY al.created_at DESC LIMIT 100";

// Thực hiện truy vấn
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Lấy danh sách người dùng cho filter
$users = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name")->fetchAll();

// Danh sách loại hành động
$action_types = [
    'login' => 'Đăng nhập',
    'logout' => 'Đăng xuất',
    'create_document' => 'Tạo tài liệu',
    'edit_document' => 'Sửa tài liệu',
    'delete_document' => 'Xóa tài liệu',
    'download_document' => 'Tải tài liệu',
    'add_comment' => 'Thêm bình luận',
    'delete_comment' => 'Xóa bình luận',
    'change_settings' => 'Thay đổi cài đặt'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử hoạt động - Admin Panel</title>
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
                    <h1 class="h2">Lịch sử hoạt động</h1>
                </div>

                <!-- Bộ lọc -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Người dùng</label>
                                <select class="form-select" name="user_id">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"
                                                <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Loại hành động</label>
                                <select class="form-select" name="action_type">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($action_types as $key => $label): ?>
                                        <option value="<?php echo $key; ?>"
                                                <?php echo $action_type == $key ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Từ ngày</label>
                                <input type="date" class="form-control" name="date_from"
                                       value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Đến ngày</label>
                                <input type="date" class="form-control" name="date_to"
                                       value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">
                                    <i class="fas fa-filter me-2"></i>Lọc
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bảng lịch sử -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Thời gian</th>
                                        <th>Người dùng</th>
                                        <th>Hành động</th>
                                        <th>Chi tiết</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activities as $activity): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i:s', strtotime($activity['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($activity['user_name']); ?></td>
                                            <td>
                                                <?php
                                                $action_label = $action_types[$activity['action_type']] ?? $activity['action_type'];
                                                $action_class = match($activity['action_type']) {
                                                    'login' => 'success',
                                                    'logout' => 'secondary',
                                                    'delete_document', 'delete_comment' => 'danger',
                                                    'create_document', 'add_comment' => 'primary',
                                                    'edit_document', 'change_settings' => 'warning',
                                                    default => 'info'
                                                };
                                                echo "<span class='badge bg-$action_class'>$action_label</span>";
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['details']); ?></td>
                                            <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
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