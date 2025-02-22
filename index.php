<?php
session_start();
include_once 'config/Database.php';
include_once 'Api/Product.php';
// include_once 'models/User.php';
// include_once 'models/Order.php';
// include_once 'models/Cart.php';
$database = new Database();
$db = $database->getConnection();

// Initialize the Product model
$product = new Product($db);

$page = isset($_GET['page']) ? $_GET['page'] : 1;
$products = $product->getProducts($page);
$total_products = $product->getTotalProducts();
$total_pages = ceil($total_products / 10);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shop Bán Hàng</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .product-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
        }
        .product-title {
            font-size: 1.2em;
            margin: 10px 0;
            color: #333;
        }
        .product-price {
            font-size: 1.1em;
            color: #e44d26;
            font-weight: bold;
            margin: 10px 0;
        }
        .product-origin {
            color: #666;
            font-size: 0.9em;
        }
        .add-to-cart {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        .add-to-cart:hover {
            background: #45a049;
        }
        .quantity-input {
            width: 60px;
            padding: 5px;
            margin-right: 10px;
        }
        .header {
            background: #333;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }
        .header a:hover {
            color: #ddd;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        .pagination a {
            padding: 8px 16px;
            margin: 0 4px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
        }
        .pagination a:hover {
            background-color: #ddd;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Shop Bán Hàng</h1>
        <div>
            <?php if(isset($_SESSION['user'])): ?>
                <span>Xin chào <?php echo $_SESSION['user']['username']; ?></span>
                <a href="logout.php">Đăng xuất</a>
            <?php else: ?>
                <a href="login.php">Đăng nhập</a>
                <a href="register.php">Đăng ký</a>
            <?php endif; ?>
            <a href="cart.php">Giỏ hàng</a>
        </div>
    </div>

    <div id="success-message" class="success-message">
        Sản phẩm đã được thêm vào giỏ hàng!
    </div>

    <div class="product-grid">
        <?php foreach($products as $product): ?>
            <div class="product-card">
                <img src="assets/images/default-product.jpg" alt="<?php echo $product['tenhang']; ?>" class="product-image">
                <h3 class="product-title"><?php echo $product['tenhang']; ?></h3>
                <p class="product-price"><?php echo number_format($product['dongia']); ?> VNĐ</p>
                <p class="product-origin">Xuất xứ: <?php echo $product['nguongoc']; ?></p>
                <p><?php echo $product['mota']; ?></p>
                <form action="add_to_cart.php" method="post" class="add-to-cart-form">
                    <input type="hidden" name="mahang" value="<?php echo $product['mahang']; ?>">
                    <input type="number" name="soluong" value="1" min="1" class="quantity-input">
                    <button type="submit" class="add-to-cart">Thêm vào giỏ</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="pagination">
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>" <?php echo ($page == $i) ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('.add-to-cart-form');
        const successMessage = document.getElementById('success-message');

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => response.text())
                .then(() => {
                    successMessage.style.display = 'block';
                    setTimeout(() => {
                        successMessage.style.display = 'none';
                    }, 3000);
                });
            });
        });
    });
    </script>
</body>
</html>