<?php
session_start();
include 'config.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>XLight - Trang chủ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .main-content {
            background-image: url('images/background.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: calc(100vh - 56px);
            margin-top: 56px; 
            position: relative;
            z-index: 1;
        }

        /* Navbar styles */
        .navbar {
            background: #e6e6fa !important; /* Màu tím nhạt */
            position: fixed; /* Fixed navbar */
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
        }
        .navbar-brand {
            font-weight: bold;
            color: #6a5acd !important; /* Màu tím đậm hơn cho brand */
        }
        .nav-link {
            color: #6a5acd !important; /* Màu tím đậm hơn cho link */
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: #483d8b !important; /* Màu tím đậm khi hover */
            transform: translateY(-2px);
        }
        .navbar-toggler {
            border-color: #6a5acd;
        }
        .navbar-toggler-icon {
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border: none;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .carousel-item img {
            height: 300px;
            object-fit: cover;
            width: 100%;
        }
        .product-title {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .carousel-caption {
            background: rgba(0,0,0,0.7);
            padding: 15px;
            border-radius: 10px;
            bottom: 20px;
            backdrop-filter: blur(5px);
        }
        #productCarousel {
            margin-top: 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .section-title {
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            margin-bottom: 30px;
        }
        .container {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .dropdown-menu {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .dropdown-item {
            color: #6a5acd !important; 
        }
        .dropdown-item:hover {
            color: #6a5acd !important; 
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="XLight Logo" height="50">
                XLight
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="user/customer.php">Hàng hóa</a>
                    </li>
                    <?php if(isset($_SESSION['USER'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="user/cart.php">
                                <i class="fas fa-shopping-cart"></i> Giỏ hàng
                                <?php if(!empty($_SESSION['cart'])): ?>
                                    <span class="badge bg-danger"><?php echo count($_SESSION['cart']); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user/donhang.php">
                                <i class="fas fa-file-invoice"></i> Đơn hàng
                            </a>
                        </li>
                        <?php if($_SESSION['USER']['role'] == 'admin'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" 
                                   role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cog"></i> Quản lý
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="admin/manage_products.php">
                                            <i class="fas fa-box"></i> Quản lý hàng hóa
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="admin/manage_users.php">
                                            <i class="fas fa-users"></i> Quản lý tài khoản
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="admin/manage_orders.php">
                                            <i class="fas fa-file-invoice-dollar"></i> Quản lý đơn hàng
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['USER'])): ?>
                        <li class="nav-item">
                            <span class="nav-link">
                                <i class="fas fa-user"></i> 
                                <?php echo htmlspecialchars($_SESSION['USER']['username']); ?>
                                <?php if($_SESSION['USER']['role'] == 'admin'): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user/userlogin.php?reqact=userlogout">
                                <i class="fas fa-sign-out-alt"></i> Đăng xuất
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="user/userlogin.php">
                                <i class="fas fa-sign-in-alt"></i> Đăng nhập
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>


    <!-- Main Content -->
    <div class="container">
        <h3 class="text-center mb-5 mt-5">Sản phẩm nổi bật</h3>
        
        <!-- Sử dụng row-cols để đảm bảo số cột đều nhau -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php
            try {
                $sql = "SELECT * FROM products ORDER BY id DESC";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                
                while ($product = $stmt->fetch()) {
                    ?>
                    <div class="col d-flex align-items-stretch">
                        <div class="card w-100"> <!-- Thêm w-100 để card full width -->
                            <!-- Ảnh sản phẩm -->
                            <div class="card-img-wrapper" style="height: 200px; overflow: hidden;">
                                <img src="images/<?php echo $product['image']; ?>" 
                                     class="card-img-top h-100 w-100"
                                     alt="<?php echo $product['name']; ?>"
                                     style="object-fit: cover;">
                            </div>
                            
                            <!-- Thông tin sản phẩm -->
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title product-title mb-3">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h5>
                                
                                <!-- Đẩy giá và nút xuống dưới cùng -->
                                <div class="mt-auto">
                                    <p class="card-text text-danger fw-bold fs-5 mb-3">
                                        <?php echo number_format($product['price'], 0, ',', '.'); ?>đ
                                    </p>
                                    <?php if($product['quantity'] > 0): ?>
                                        <a href="user/cart.php?action=add&id=<?php echo $product['id']; ?>" 
                                           class="btn btn-primary w-100">
                                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary w-100" disabled>
                                            Hết hàng
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } catch(PDOException $e) {
                echo '<div class="alert alert-danger">Lỗi: ' . $e->getMessage() . '</div>';
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
