<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sử dụng đường dẫn tuyệt đối
$root = dirname(dirname(__FILE__));
require_once $root . '/config/Database.php';
require_once $root . '/models/User.php';

// Kiểm tra quyền admin
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Lấy danh sách bảng để quản lý
$tables = [
    'tbKhachHang' => 'Khách hàng',
    'tbMatHang' => 'Mặt hàng',
    'tbDonHang' => 'Đơn hàng',
    'tbChiTietDonHang' => 'Chi tiết đơn hàng',
    'tbUser' => 'Người dùng'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quản trị hệ thống</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container {
            padding: 20px;
        }
        .table-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .table-card {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .table-card a {
            text-decoration: none;
            color: #333;
        }
        .table-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div id="header">
        Xin chào <?php echo $_SESSION['user']['username']; ?> |
        <a href="../logout.php">Đăng xuất</a>
    </div>

    <div class="admin-container">
        <h2>Quản lý dữ liệu</h2>
        <div class="table-list">
            <?php foreach($tables as $table => $name): ?>
            <div class="table-card">
                <a href="manage.php?table=<?php echo $table; ?>">
                    <h3><?php echo $name; ?></h3>
                    <p>Quản lý <?php echo $name; ?></p>
                </a>
            </div>
            <?php endforeach; ?>
            <div class="table-card">
                <a href="manage_products.php">
                    <h3>Quản lý sản phẩm</h3>
                    <p>Thêm và xem danh sách sản phẩm</p>
                </a>
            </div>
        </div>
    </div>
</body>
</html>