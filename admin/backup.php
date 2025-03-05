<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Auth.php';

$auth = new Auth($conn);
$auth->requireAdmin();

// Xử lý tạo backup
if (isset($_POST['action']) && $_POST['action'] === 'create_backup') {
    try {
        // Tạo thư mục backup nếu chưa tồn tại
        $backup_dir = __DIR__ . '/../backups';
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0777, true);
        }

        // Tên file backup
        $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

        // Command để backup (MySQL)
        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s',
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            $backup_file
        );

        // Thực hiện backup
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            $_SESSION['success'] = "Đã tạo backup thành công!";
        } else {
            throw new Exception("Lỗi khi tạo backup");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
    header("Location: backup.php");
    exit();
}

// Xử lý khôi phục backup
if (isset($_POST['action']) && $_POST['action'] === 'restore_backup') {
    try {
        $backup_file = $_POST['backup_file'] ?? '';
        if (empty($backup_file)) {
            throw new Exception("Không tìm thấy file backup");
        }

        $full_path = __DIR__ . '/../backups/' . basename($backup_file);
        if (!file_exists($full_path)) {
            throw new Exception("File backup không tồn tại");
        }

        // Command để restore (MySQL)
        $command = sprintf(
            'mysql -h %s -u %s -p%s %s < %s',
            DB_HOST,
            DB_USER,
            DB_PASS,
            DB_NAME,
            $full_path
        );

        // Thực hiện restore
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            $_SESSION['success'] = "Đã khôi phục dữ liệu thành công!";
        } else {
            throw new Exception("Lỗi khi khôi phục dữ liệu");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
    header("Location: backup.php");
    exit();
}

// Xử lý xóa backup
if (isset($_POST['action']) && $_POST['action'] === 'delete_backup') {
    try {
        $backup_file = $_POST['backup_file'] ?? '';
        if (empty($backup_file)) {
            throw new Exception("Không tìm thấy file backup");
        }

        $full_path = __DIR__ . '/../backups/' . basename($backup_file);
        if (file_exists($full_path)) {
            unlink($full_path);
            $_SESSION['success'] = "Đã xóa file backup thành công!";
        } else {
            throw new Exception("File backup không tồn tại");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Lỗi: " . $e->getMessage();
    }
    header("Location: backup.php");
    exit();
}

// Lấy danh sách file backup
$backup_files = [];
$backup_dir = __DIR__ . '/../backups';
if (file_exists($backup_dir)) {
    $files = glob($backup_dir . '/backup_*.sql');
    foreach ($files as $file) {
        $backup_files[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'date' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
    // Sắp xếp theo thời gian mới nhất
    usort($backup_files, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sao lưu & Khôi phục - Admin Panel</title>
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
                    <h1 class="h2">Sao lưu & Khôi phục</h1>
                    <form method="POST" action="" class="d-flex gap-2">
                        <input type="hidden" name="action" value="create_backup">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Tạo backup mới
                        </button>
                    </form>
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

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Tên file</th>
                                        <th>Kích thước</th>
                                        <th>Ngày tạo</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($backup_files)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Chưa có file backup nào</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($backup_files as $file): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($file['name']); ?></td>
                                                <td><?php echo number_format($file['size'] / 1024, 2); ?> KB</td>
                                                <td><?php echo date('d/m/Y H:i:s', strtotime($file['date'])); ?></td>
                                                <td>
                                                    <form method="POST" action="" class="d-inline">
                                                        <input type="hidden" name="action" value="restore_backup">
                                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($file['name']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning"
                                                                onclick="return confirm('Bạn có chắc chắn muốn khôi phục dữ liệu từ backup này?')">
                                                            <i class="fas fa-undo"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="" class="d-inline">
                                                        <input type="hidden" name="action" value="delete_backup">
                                                        <input type="hidden" name="backup_file" value="<?php echo htmlspecialchars($file['name']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger"
                                                                onclick="return confirm('Bạn có chắc chắn muốn xóa file backup này?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Hướng dẫn -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Hướng dẫn</h5>
                    </div>
                    <div class="card-body">
                        <h6>Sao lưu (Backup)</h6>
                        <p>
                            - Click nút "Tạo backup mới" để tạo một bản sao lưu mới của database.<br>
                            - File backup sẽ được lưu trong thư mục /backups với tên theo định dạng: backup_YYYY-MM-DD_HH-mm-ss.sql
                        </p>

                        <h6>Khôi phục (Restore)</h6>
                        <p>
                            - Click nút <i class="fas fa-undo"></i> bên cạnh file backup để khôi phục dữ liệu từ file đó.<br>
                            - LƯU Ý: Việc khôi phục sẽ ghi đè lên dữ liệu hiện tại. Hãy chắc chắn bạn muốn thực hiện điều này.
                        </p>

                        <h6>Xóa backup</h6>
                        <p>
                            - Click nút <i class="fas fa-trash"></i> để xóa file backup không cần thiết.<br>
                            - Nên giữ lại ít nhất một file backup gần nhất để đề phòng sự cố.
                        </p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>