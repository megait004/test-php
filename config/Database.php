<?php
class Database {
    private $host = 'localhost';
    private $port = '5432';
    private $dbname = 'postgres';
    private $username = 'postgres';
    private $password = 'postgres';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "pgsql:host=$this->host;port=$this->port;dbname=$this->dbname",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Tạo các bảng nếu chưa tồn tại
            $this->createTables();

            return $this->conn;
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
            return null;
        }
    }

    private function createTables() {
        // Tạo bảng Khách hàng
        $this->conn->exec("CREATE TABLE IF NOT EXISTS tbKhachHang (
            makhach VARCHAR(20) PRIMARY KEY,
            tenkhach VARCHAR(100) NOT NULL,
            tuoi INTEGER,
            gioitinh VARCHAR(10),
            sodienthoai VARCHAR(15),
            diachi TEXT
        )");

        // Tạo bảng Mặt hàng
        $this->conn->exec("CREATE TABLE IF NOT EXISTS tbMatHang (
            mahang VARCHAR(20) PRIMARY KEY,
            tenhang VARCHAR(100) NOT NULL,
            mota TEXT,
            dongia DECIMAL(15,2) NOT NULL,
            nguongoc VARCHAR(100)
        )");

        // Tạo bảng Đơn hàng
        $this->conn->exec("CREATE TABLE IF NOT EXISTS tbDonHang (
            madonhang VARCHAR(20) PRIMARY KEY,
            makhach VARCHAR(20) REFERENCES tbKhachHang(makhach),
            ngaymua DATE NOT NULL,
            tinhtrang VARCHAR(50)
        )");

        // Tạo bảng Chi tiết đơn hàng
        $this->conn->exec("CREATE TABLE IF NOT EXISTS tbChiTietDonHang (
            machitiet VARCHAR(20) PRIMARY KEY,
            madonhang VARCHAR(20) REFERENCES tbDonHang(madonhang),
            mahang VARCHAR(20) REFERENCES tbMatHang(mahang),
            soluong INTEGER NOT NULL,
            dongia DECIMAL(15,2) NOT NULL
        )");

        // Tạo bảng User
        $this->conn->exec("CREATE TABLE IF NOT EXISTS tbUser (
            username VARCHAR(50) PRIMARY KEY,
            password VARCHAR(255) NOT NULL,
            active BOOLEAN DEFAULT true
        )");

        // Tạo bảng Role
        $this->conn->exec("CREATE TABLE IF NOT EXISTS tbRole (
            role VARCHAR(20) PRIMARY KEY,
            description TEXT
        )");

        // Tạo bảng UserInRole
        $this->conn->exec("CREATE TABLE IF NOT EXISTS tbUserInRole (
            username VARCHAR(50) REFERENCES tbUser(username),
            role VARCHAR(20) REFERENCES tbRole(role),
            PRIMARY KEY (username, role)
        )");

        // Thêm roles mặc định
        $this->conn->exec("INSERT INTO tbRole (role, description) VALUES
            ('Admin', 'Quản trị viên hệ thống'),
            ('Member', 'Thành viên thông thường')
            ON CONFLICT (role) DO NOTHING");
    }
}
?>