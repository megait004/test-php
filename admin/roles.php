<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';

$auth = new Auth($conn);
$auth->requireAdmin();

// Xử lý thêm/sửa/xóa role
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_role':
                $role_name = $_POST['role_name'] ?? '';
                $permissions = $_POST['permissions'] ?? [];

                if (!empty($role_name)) {
                    try {
                        $stmt = $conn->prepare("INSERT INTO roles (name) VALUES (?)");
                        $stmt->execute([$role_name]);
                        $role_id = $conn->lastInsertId();

                        // Thêm quyền cho role
                        if (!empty($permissions)) {
                            $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission) VALUES (?, ?)");
                            foreach ($permissions as $permission) {
                                $stmt->execute([$role_id, $permission]);
                            }
                        }
                        $_SESSION['success'] = "Đã thêm vai trò mới thành công!";
                    } catch (PDOException $e) {
                        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
                    }
                }
                break;

            case 'edit_role':
                $role_id = $_POST['role_id'] ?? 0;
                $role_name = $_POST['role_name'] ?? '';
                $permissions = $_POST['permissions'] ?? [];

                if ($role_id && !empty($role_name)) {
                    try {
                        $conn->beginTransaction();

                        // Cập nhật tên role
                        $stmt = $conn->prepare("UPDATE roles SET name = ? WHERE id = ?");
                        $stmt->execute([$role_name, $role_id]);

                        // Xóa quyền cũ
                        $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
                        $stmt->execute([$role_id]);

                        // Thêm quyền mới
                        if (!empty($permissions)) {
                            $stmt = $conn->prepare("INSERT INTO role_permissions (role_id, permission) VALUES (?, ?)");
                            foreach ($permissions as $permission) {
                                $stmt->execute([$role_id, $permission]);
                            }
                        }

                        $conn->commit();
                        $_SESSION['success'] = "Đã cập nhật vai trò thành công!";
                    } catch (PDOException $e) {
                        $conn->rollBack();
                        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
                    }
                }
                break;

            case 'delete_role':
                $role_id = $_POST['role_id'] ?? 0;
                if ($role_id) {
                    try {
                        $stmt = $conn->prepare("DELETE FROM roles WHERE id = ?");
                        $stmt->execute([$role_id]);
                        $_SESSION['success'] = "Đã xóa vai trò thành công!";
                    } catch (PDOException $e) {
                        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
                    }
                }
                break;
        }
    }
    header("Location: roles.php");
    exit();
}

// Lấy danh sách roles
$roles = $conn->query("
    SELECT r.*, GROUP_CONCAT(DISTINCT rp.permission SEPARATOR ',') as permissions
    FROM roles r
    LEFT JOIN role_permissions rp ON r.id = rp.role_id
    GROUP BY r.id
    ORDER BY r.name ASC
")->fetchAll();

// Danh sách quyền có sẵn
$available_permissions = [
    'view_documents' => 'Xem tài liệu',
    'upload_documents' => 'Tải lên tài liệu',
    'edit_documents' => 'Sửa tài liệu',
    'delete_documents' => 'Xóa tài liệu',
    'manage_users' => 'Quản lý người dùng',
    'manage_roles' => 'Quản lý vai trò',
    'manage_settings' => 'Quản lý cài đặt'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phân quyền - Admin Panel</title>
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
                    <h1 class="h2">Quản lý phân quyền</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        <i class="fas fa-plus me-2"></i>Thêm vai trò mới
                    </button>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên vai trò</th>
                                <th>Quyền hạn</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                                <tr>
                                    <td><?php echo $role['id']; ?></td>
                                    <td><?php echo htmlspecialchars($role['name']); ?></td>
                                    <td>
                                        <?php
                                        $role_permissions = !empty($role['permissions']) ? explode(',', $role['permissions']) : [];
                                        foreach ($role_permissions as $permission) {
                                            if (isset($available_permissions[$permission])) {
                                                echo '<span class="badge bg-info me-1">' . $available_permissions[$permission] . '</span>';
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary me-1"
                                                onclick="editRole(<?php echo htmlspecialchars(json_encode($role)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger"
                                                onclick="deleteRole(<?php echo $role['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal thêm vai trò -->
    <div class="modal fade" id="addRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm vai trò mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="add_role">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên vai trò</label>
                            <input type="text" class="form-control" name="role_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quyền hạn</label>
                            <?php foreach ($available_permissions as $key => $label): ?>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="permissions[]" value="<?php echo $key; ?>" id="add_<?php echo $key; ?>">
                                    <label class="form-check-label" for="add_<?php echo $key; ?>"><?php echo $label; ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary">Thêm vai trò</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal sửa vai trò -->
    <div class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sửa vai trò</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="edit_role">
                    <input type="hidden" name="role_id" id="edit_role_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên vai trò</label>
                            <input type="text" class="form-control" name="role_name" id="edit_role_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quyền hạn</label>
                            <?php foreach ($available_permissions as $key => $label): ?>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="permissions[]" value="<?php echo $key; ?>" id="edit_<?php echo $key; ?>">
                                    <label class="form-check-label" for="edit_<?php echo $key; ?>"><?php echo $label; ?></label>
                                </div>
                            <?php endforeach; ?>
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

    <!-- Form xóa vai trò (ẩn) -->
    <form id="deleteRoleForm" action="" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete_role">
        <input type="hidden" name="role_id" id="delete_role_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editRole(role) {
            document.getElementById('edit_role_id').value = role.id;
            document.getElementById('edit_role_name').value = role.name;

            // Reset all checkboxes
            document.querySelectorAll('#editRoleModal input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });

            // Check permissions
            if (role.permissions) {
                const permissions = role.permissions.split(',').filter(Boolean);
                permissions.forEach(permission => {
                    const checkbox = document.getElementById('edit_' + permission);
                    if (checkbox) checkbox.checked = true;
                });
            }

            new bootstrap.Modal(document.getElementById('editRoleModal')).show();
        }

        function deleteRole(roleId) {
            if (confirm('Bạn có chắc chắn muốn xóa vai trò này?')) {
                document.getElementById('delete_role_id').value = roleId;
                document.getElementById('deleteRoleForm').submit();
            }
        }
    </script>
</body>
</html>