<?php
session_start();
include '../config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['USER']) || $_SESSION['USER']['role'] != 'admin') {
    header('Location: ../user/userlogin.php');
    exit();
}

// Xử lý thêm sản phẩm
if (isset($_GET['action']) && $_GET['action'] == 'add') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    
    // Xử lý upload ảnh
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image = time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "../images/" . $image);
        
        // Thêm vào database
        $sql = "INSERT INTO products (name, price, quantity, image) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$name, $price, $quantity, $image]);
    }
    header('Location: manage_products.php');
    exit();
}

// Xử lý xóa sản phẩm
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id = $_GET['id'];
    
    // Xóa ảnh cũ
    $sql = "SELECT image FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if ($product && file_exists("../images/" . $product['image'])) {
        unlink("../images/" . $product['image']);
    }
    
    // Xóa từ database
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    
    header('Location: manage_products.php');
    exit();
}

// Lấy danh sách sản phẩm
$sql = "SELECT * FROM products ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll();

// Hiển thị view
include 'manage_products_view.php';
?>