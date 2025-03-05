<?php
ob_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';

$auth = new Auth($conn);
$auth->requireAdmin();

// Lấy các tham số lọc
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$activity_type = $_GET['type'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Chuẩn bị câu truy vấn cơ bản
$query = "
    SELECT a.*, u.full_name as user_name
    FROM activities a
    JOIN users u ON a.user_id = u.id
    WHERE 1=1
";
$params = [];

// Thêm điều kiện lọc
if ($user_id > 0) {
    $query .= " AND a.user_id = ?";
    $params[] = $user_id;
}

if ($activity_type) {
    $query .= " AND a.type = ?";
    $params[] = $activity_type;
}

$query .= " AND DATE(a.created_at) BETWEEN ? AND ?";
$params[] = $start_date;
$params[] = $end_date;

// Thêm sắp xếp
$query .= " ORDER BY a.created_at DESC";

// Thực hiện truy vấn
$stmt = $conn->prepare($query);
$stmt->execute($params);
$activities = $stmt->fetchAll();

// Lấy danh sách người dùng cho bộ lọc
$users = $conn->query("SELECT id, full_name FROM users ORDER BY full_name")->fetchAll();

// Lấy danh sách loại hoạt động cho bộ lọc
$activity_types = $conn->query("SELECT DISTINCT type FROM activities ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);

?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Lịch sử hoạt động</h1>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Người dùng</label>
                <select name="user_id" class="form-select">
                    <option value="">Tất cả người dùng</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['id']; ?>" <?php echo $user_id == $u['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u['full_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Loại hoạt động</label>
                <select name="type" class="form-select">
                    <option value="">Tất cả hoạt động</option>
                    <?php foreach ($activity_types as $type): ?>
                        <option value="<?php echo $type; ?>" <?php echo $activity_type == $type ? 'selected' : ''; ?>>
                            <?php echo ucfirst($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Từ ngày</label>
                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Đến ngày</label>
                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Lọc
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Activities Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Người dùng</th>
                        <th>Hoạt động</th>
                        <th>Chi tiết</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activities)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Không có hoạt động nào trong khoảng thời gian này.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i:s', strtotime($activity['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($activity['user_name']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getActivityBadgeClass($activity['type']); ?>">
                                    <?php echo ucfirst($activity['type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($activity['description']); ?></td>
                            <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
function getActivityBadgeClass($type) {
    switch ($type) {
        case 'login':
            return 'primary';
        case 'upload':
            return 'success';
        case 'download':
            return 'info';
        case 'comment':
            return 'warning';
        case 'like':
            return 'danger';
        default:
            return 'secondary';
    }
}

$content = ob_get_clean();
require_once 'includes/admin_layout.php';
?>