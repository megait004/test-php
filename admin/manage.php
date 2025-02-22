<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sử dụng đường dẫn tuyệt đối
$root = dirname(dirname(__FILE__));
require_once $root . '/config/Database.php';

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$table = $_GET['table'] ?? '';
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

// Lấy thông tin cấu trúc bảng
$stmt = $db->query("SELECT column_name, data_type, character_maximum_length
                    FROM information_schema.columns
                    WHERE table_name = '$table'");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xử lý các action
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if($action == 'add' || $action == 'edit') {
            $data = [];
            $params = [];
            foreach($columns as $col) {
                $colName = $col['column_name'];
                if(isset($_POST[$colName])) {
                    $data[] = "$colName = :$colName";
                    $params[":$colName"] = $_POST[$colName];
                }
            }

            if($action == 'add') {
                $sql = "INSERT INTO $table SET " . implode(', ', $data);
            } else {
                $sql = "UPDATE $table SET " . implode(', ', $data) . " WHERE " . key($params) . " = :" . key($params);
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            header("Location: manage.php?table=$table");
            exit();
        }

        if($action == 'delete' && $id) {
            $stmt = $db->prepare("DELETE FROM $table WHERE " . $columns[0]['column_name'] . " = :id");
            $stmt->execute([':id' => $id]);
            header("Location: manage.php?table=$table");
            exit();
        }
    } catch(Exception $e) {
        $error = $e->getMessage();
    }
}

// Lấy dữ liệu cho danh sách
$data = [];
if($action == 'list') {
    $stmt = $db->query("SELECT * FROM $table");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lấy dữ liệu cho edit
$editData = [];
if($action == 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM $table WHERE " . $columns[0]['column_name'] . " = :id");
    $stmt->execute([':id' => $id]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quản lý <?php echo $table; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container { padding: 20px; }
        .data-table { width: 100%; margin-top: 20px; }
        .data-table th, .data-table td { padding: 10px; text-align: left; }
        .form-container { max-width: 600px; margin: 20px auto; }
        .action-buttons { margin-top: 20px; }
        .action-buttons a { margin-right: 10px; }
    </style>
</head>
<body>
    <div id="header">
        <a href="index.php">Quay lại Dashboard</a> |
        <a href="../logout.php">Đăng xuất</a>
    </div>

    <div class="admin-container">
        <h2>Quản lý <?php echo $table; ?></h2>

        <?php if(isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($action == 'list'): ?>
            <div class="action-buttons">
                <a href="?table=<?php echo $table; ?>&action=add" class="btn">Thêm mới</a>
            </div>

            <table class="data-table">
                <tr>
                    <?php foreach($columns as $col): ?>
                        <th><?php echo $col['column_name']; ?></th>
                    <?php endforeach; ?>
                    <th>Thao tác</th>
                </tr>
                <?php foreach($data as $row): ?>
                    <tr>
                        <?php foreach($columns as $col): ?>
                            <td><?php echo $row[$col['column_name']]; ?></td>
                        <?php endforeach; ?>
                        <td>
                            <a href="?table=<?php echo $table; ?>&action=edit&id=<?php echo $row[$columns[0]['column_name']]; ?>">Sửa</a> |
                            <a href="?table=<?php echo $table; ?>&action=delete&id=<?php echo $row[$columns[0]['column_name']]; ?>"
                               onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>

        <?php else: ?>
            <div class="form-container">
                <form method="post">
                    <?php foreach($columns as $col): ?>
                        <?php if($col['column_name'] != 'id'): ?>
                            <div class="form-group">
                                <label><?php echo $col['column_name']; ?>:</label>
                                <input type="text" name="<?php echo $col['column_name']; ?>"
                                       value="<?php echo $editData[$col['column_name']] ?? ''; ?>" required>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <button type="submit" class="btn">Lưu</button>
                    <a href="?table=<?php echo $table; ?>" class="btn">Hủy</a>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>