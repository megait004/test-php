<?php
class Cart {
    private $conn;
    private $table = "tbChiTietDonHang";
    private $table_product = "tbMatHang";
    private $table_order = "tbDonHang";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getCart() {
        if(!isset($_SESSION['user'])) {
            return [];
        }

        $makhach = $_SESSION['user']['username'];

        $query = "SELECT c.machitiet, c.mahang, c.soluong, c.dongia,
                        p.tenhang,
                        (c.soluong * c.dongia) as thanhtien
                 FROM " . $this->table . " c
                 LEFT JOIN " . $this->table_product . " p ON p.mahang = c.mahang
                 LEFT JOIN " . $this->table_order . " o ON o.madonhang = c.madonhang
                 WHERE o.makhach = :makhach AND o.tinhtrang = 'pending'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':makhach', $makhach);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotal() {
        if(!isset($_SESSION['user'])) {
            return 0;
        }

        $makhach = $_SESSION['user']['username'];

        $query = "SELECT SUM(c.soluong * c.dongia) as total
                 FROM " . $this->table . " c
                 LEFT JOIN " . $this->table_order . " o ON o.madonhang = c.madonhang
                 WHERE o.makhach = :makhach AND o.tinhtrang = 'pending'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':makhach', $makhach);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function addToCart($mahang, $soluong) {
        if(!isset($_SESSION['user'])) {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $makhach = $_SESSION['user']['username'];

            // Check if pending order exists
            $query = "SELECT madonhang FROM " . $this->table_order . "
                     WHERE makhach = :makhach AND tinhtrang = 'pending'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':makhach', $makhach);
            $stmt->execute();
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$order) {
                // Create new order
                $madonhang = uniqid();
                $query = "INSERT INTO " . $this->table_order . "
                         (madonhang, makhach, ngaymua, tinhtrang)
                         VALUES (:madonhang, :makhach, CURRENT_DATE, 'pending')";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':madonhang', $madonhang);
                $stmt->bindParam(':makhach', $makhach);
                $stmt->execute();
            } else {
                $madonhang = $order['madonhang'];
            }

            // Get product price
            $query = "SELECT dongia FROM " . $this->table_product . " WHERE mahang = :mahang";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':mahang', $mahang);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            // Add to cart
            $machitiet = uniqid();
            $query = "INSERT INTO " . $this->table . "
                     (machitiet, madonhang, mahang, soluong, dongia)
                     VALUES (:machitiet, :madonhang, :mahang, :soluong, :dongia)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':machitiet', $machitiet);
            $stmt->bindParam(':madonhang', $madonhang);
            $stmt->bindParam(':mahang', $mahang);
            $stmt->bindParam(':soluong', $soluong);
            $stmt->bindParam(':dongia', $product['dongia']);

            $result = $stmt->execute();
            $this->conn->commit();
            return $result;

        } catch(PDOException $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function updateCart($machitiet, $soluong) {
        if(!isset($_SESSION['user'])) {
            return false;
        }

        $query = "UPDATE " . $this->table . "
                 SET soluong = :soluong
                 WHERE machitiet = :machitiet";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':soluong', $soluong);
        $stmt->bindParam(':machitiet', $machitiet);

        return $stmt->execute();
    }

    public function removeFromCart($machitiet) {
        if(!isset($_SESSION['user'])) {
            return false;
        }

        $query = "DELETE FROM " . $this->table . " WHERE machitiet = :machitiet";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':machitiet', $machitiet);

        return $stmt->execute();
    }

    public function checkout() {
        if(!isset($_SESSION['user'])) {
            return false;
        }

        $makhach = $_SESSION['user']['username'];

        $query = "UPDATE " . $this->table_order . "
                 SET tinhtrang = 'completed'
                 WHERE makhach = :makhach AND tinhtrang = 'pending'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':makhach', $makhach);

        return $stmt->execute();
    }
}
