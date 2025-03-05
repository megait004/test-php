<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';

$auth = new Auth($conn);
$auth->requireAdmin();

// Xử lý xóa người dùng
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $user_id = $_POST['user_id'] ?? 0;
    if ($user_id != $_SESSION['user_id']) { // Không cho phép xóa chính mình
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
    }
    header("Location: users.php");
    exit();
}

// Xử lý thay đổi vai trò
if (isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $user_id = $_POST['user_id'] ?? 0;
    $new_role_id = $_POST['role_id'] ?? '';
    if ($user_id != $_SESSION['user_id']) {
        $stmt = $conn->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        $stmt->execute([$new_role_id, $user_id]);
    }
    header("Location: users.php");
    exit();
}

// Lấy danh sách người dùng với thống kê
$users = $conn->query("
    SELECT u.*,
           r.name as role_name,
           COUNT(DISTINCT d.id) as document_count,
           COUNT(DISTINCT c.id) as comment_count,
           COUNT(DISTINCT l.id) as like_count
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
    LEFT JOIN documents d ON u.id = d.user_id
    LEFT JOIN comments c ON u.id = c.user_id
    LEFT JOIN likes l ON u.id = l.user_id
    GROUP BY u.id
    ORDER BY u.id DESC
")->fetchAll();

// Lấy danh sách roles cho dropdown
$roles = $conn->query("SELECT * FROM roles ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - Hệ thống quản lý tài liệu</title>
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
                    <h1 class="h2">Quản lý người dùng</h1>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Họ tên</th>
                                        <th>Email</th>
                                        <th>Vai trò</th>
                                        <th>Thống kê</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="action" value="change_role">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="role_id" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                                    <?php foreach ($roles as $role): ?>
                                                        <option value="<?php echo $role['id']; ?>"
                                                                <?php echo $user['role_id'] == $role['id'] ? 'selected' : ''; ?>>
                                                            <?php echo ucfirst($role['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                            <?php else: ?>
                                            <span class="badge bg-primary"><?php echo ucfirst($user['role_name']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-file-alt text-primary"></i> <?php echo $user['document_count']; ?>
                                            <i class="fas fa-comment text-success ms-2"></i> <?php echo $user['comment_count']; ?>
                                            <i class="fas fa-heart text-danger ms-2"></i> <?php echo $user['like_count']; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
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
</body>
</html>