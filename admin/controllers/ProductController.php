<?php
class ProductController {
    private $db;
    private $productModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->productModel = new Product($this->db);
    }

    public function index() {
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $products = $this->productModel->getProducts($page);
        $total_pages = ceil($this->productModel->getTotalProducts() / 10);

        require_once 'app/views/products/index.php';
    }

    public function add() {
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Xử lý thêm sản phẩm
        }
        require_once 'app/views/products/add.php';
    }
}