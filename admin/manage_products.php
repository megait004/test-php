<?php
session_start();
require_once '../config/Database.php';

// Kiểm tra quyền admin
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header('Location: ../login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Xử lý thêm sản phẩm mới
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $mahang = uniqid();
        $query = "INSERT INTO tbMatHang (mahang, tenhang, mota, dongia, nguongoc)
                 VALUES (:mahang, :tenhang, :mota, :dongia, :nguongoc)";
        $stmt = $db->prepare($query);

        $stmt->bindParam(':mahang', $mahang);
        $stmt->bindParam(':tenhang', $_POST['tenhang']);
        $stmt->bindParam(':mota', $_POST['mota']);
        $stmt->bindParam(':dongia', $_POST['dongia']);
        $stmt->bindParam(':nguongoc', $_POST['nguongoc']);

        $stmt->execute();
        $success = "Thêm sản phẩm thành công!";
    } catch(PDOException $e) {
        $error = "Lỗi: " . $e->getMessage();
    }
}

// Lấy danh sách sản phẩm
$query = "SELECT * FROM tbMatHang ORDER BY mahang DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quản lý sản phẩm</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .product-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .product-list {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .success {
            color: green;
            padding: 10px;
            margin-bottom: 10px;
            background: #d4edda;
            border-radius: 4px;
        }
        .error {
            color: red;
            padding: 10px;
            margin-bottom: 10px;
            background: #f8d7da;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h2>Quản lý sản phẩm</h2>

        <?php if(isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="product-form">
            <h3>Thêm sản phẩm mới</h3>
            <form method="post" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Tên hàng:</label>
                        <input type="text" name="tenhang" required>
                    </div>
                    <div class="form-group">
                        <label>Đơn giá:</label>
                        <input type="number" name="dongia" required min="0">
                    </div>
                    <div class="form-group">
                        <label>Nguồn gốc:</label>
                        <input type="text" name="nguongoc">
                    </div>
                </div>
                <div class="form-group">
                    <label>Mô tả:</label>
                    <textarea name="mota" rows="4"></textarea>
                </div>
                <button type="submit" class="btn">Thêm sản phẩm</button>
            </form>
        </div>

        <div class="product-list">
            <h3>Danh sách sản phẩm</h3>
            <table class="cart-table">
                <tr>
                    <th>Mã hàng</th>
                    <th>Tên hàng</th>
                    <th>Đơn giá</th>
                    <th>Nguồn gốc</th>
                    <th>Mô tả</th>
                </tr>
                <?php foreach($products as $product): ?>
                    <tr>
                        <td><?php echo $product['mahang']; ?></td>
                        <td><?php echo $product['tenhang']; ?></td>
                        <td><?php echo number_format($product['dongia']); ?> VNĐ</td>
                        <td><?php echo $product['nguongoc']; ?></td>
                        <td><?php echo $product['mota']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>