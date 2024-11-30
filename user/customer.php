<?php
session_start();
include '../config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['USER'])) {
    header('Location: userlogin.php');
    exit();
}

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? $_GET['search'] : '';
try {
    if ($search) {
        $sql = "SELECT * FROM products WHERE name LIKE ? ORDER BY id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['%' . $search . '%']);
    } else {
        $sql = "SELECT * FROM products ORDER BY id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    echo '<div class="alert alert-danger">Lỗi: ' . $e->getMessage() . '</div>';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>XLight - Trang khách hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <img src="../images/logo.png" alt="XLight Logo" height="40">
                XLight
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Trang chủ</a>
                    </li>
                   
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Giỏ hàng
                            <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                                <span class="badge bg-danger rounded-pill">
                                    <?php echo count($_SESSION['cart']); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if(isset($_SESSION['USER']) && $_SESSION['USER']['role'] == 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-cog"></i> Quản lý
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="../admin/manage_products.php">Quản lý hàng hóa</a></li>
                                <li><a class="dropdown-item" href="../admin/manage_users.php">Quản lý tài khoản</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user"></i> 
                            <?php echo htmlspecialchars($_SESSION['USER']['username']); ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="userlogin.php?reqact=userlogout">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <!-- Tạo khoảng trống phía trên -->
        <div style="height: 40px;"></div>

        <!-- Thanh tìm kiếm -->
        <div class="container">
            <div class="row mb-5 mt-5">
                <div class="col-md-8 mx-auto">
                    <h3 class="text-center mb-4">Danh sách hàng hóa</h3>
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Tìm kiếm sản phẩm..."
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                        <?php if($search): ?>
                            <a href="customer.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Xóa tìm kiếm
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Kết quả tìm kiếm -->
            <?php if($search): ?>
                <div class="alert alert-info text-center mb-4">
                    Kết quả tìm kiếm cho: <strong><?php echo htmlspecialchars($search); ?></strong>
                    (<?php echo count($products); ?> sản phẩm)
                </div>
            <?php endif; ?>

            <!-- Danh sách sản phẩm -->
            <div class="row row-cols-1 row-cols-md-4 g-4 mb-5">
                <?php foreach($products as $product): ?>
                <div class="col">
                    <div class="card h-100">
                        <img src="../images/<?php echo $product['image']; ?>" 
                             class="card-img-top" 
                             alt="<?php echo $product['name']; ?>"
                             style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-danger fw-bold">
                                <?php echo number_format($product['price'], 0, ',', '.'); ?>đ
                            </p>
                            <?php if($product['quantity'] > 0): ?>
                                <a href="cart.php?action=add&id=<?php echo $product['id']; ?>" 
                                   class="btn btn-primary w-100">
                                    <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="fas fa-times"></i> Hết hàng
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if(empty($products)): ?>
                <div class="alert alert-warning text-center mb-4">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Không tìm thấy sản phẩm nào!
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>