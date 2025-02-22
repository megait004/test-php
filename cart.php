<?php

require_once 'config/Database.php';
require_once 'Api/Cart.php';
$database = new Database();
$db = $database->getConnection();

$cart_items = $cart->getCart();
$total = $cart->getTotal();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Giỏ hàng</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <h2>Giỏ hàng</h2>

    <?php if(empty($cart_items)): ?>
        <div class="empty-cart">
            <p>Giỏ hàng trống</p>
        </div>
    <?php else: ?>
        <table class="cart-table">
            <tr>
                <th>Tên hàng</th>
                <th>Đơn giá</th>
                <th>Số lượng</th>
                <th>Thành tiền</th>
                <th>Thao tác</th>
            </tr>
            <?php foreach($cart_items as $item): ?>
                <tr>
                    <td><?php echo $item['tenhang']; ?></td>
                    <td><?php echo number_format($item['dongia']); ?> VNĐ</td>
                    <td>
                        <form action="update_cart.php" method="post" style="display: inline;">
                            <input type="hidden" name="mahang" value="<?php echo $item['mahang']; ?>">
                            <input type="number" name="soluong" value="<?php echo $item['soluong']; ?>" min="1"
                                   onchange="this.form.submit()" style="width: 60px;">
                        </form>
                    </td>
                    <td><?php echo number_format($item['thanhtien']); ?> VNĐ</td>
                    <td>
                        <form action="remove_from_cart.php" method="post" style="display: inline;">
                            <input type="hidden" name="mahang" value="<?php echo $item['mahang']; ?>">
                            <button type="submit" class="remove-btn">Xóa</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3"><strong>Tổng tiền:</strong></td>
                <td colspan="2"><strong><?php echo number_format($total); ?> VNĐ</strong></td>
            </tr>
        </table>

        <div class="cart-actions">
            <a href="index.php" class="btn">Tiếp tục mua hàng</a>
            <?php if(isset($_SESSION['user'])): ?>
                <form action="checkout.php" method="post" style="display: inline;">
                    <button type="submit" class="btn">Thanh toán</button>
                </form>
            <?php else: ?>
                <p>Vui lòng <a href="login.php">đăng nhập</a> để thanh toán</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html>