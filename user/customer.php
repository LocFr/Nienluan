<?php
session_start();
include '../config.php';

// Lấy tham số tìm kiếm từ URL
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Xây dựng câu truy vấn SQL
    $sql = "SELECT * FROM products WHERE 1=1";
    
    // Thêm điều kiện tìm kiếm nếu có
    if ($search) {
        $sql .= " AND name LIKE :search";
    }
    
    $stmt = $conn->prepare($sql);
    
    // Bind tham số tìm kiếm nếu có
    if ($search) {
        $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch(PDOException $e) {
    echo "Lỗi: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hàng hóa - XLight</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            padding-top: 60px;
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
        
     
        .search-form {
            width: 300px; 
             
        }
      
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top">
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
                        <a class="nav-link active" href="customer.php">
                            <i class="fas fa-box"></i> Hàng hóa
                        </a>
                    </li>
                    <?php if(isset($_SESSION['USER']) && $_SESSION['USER']['role'] == 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-cog"></i> Quản lý
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="../admin/manage_products.php">
                                        <i class="fas fa-box"></i> Quản lý hàng hóa
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="../admin/manage_users.php">
                                        <i class="fas fa-users"></i> Quản lý tài khoản
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="../admin/manage_orders.php">
                                        <i class="fas fa-file-invoice-dollar"></i> Quản lý đơn hàng
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Thanh tìm kiếm mới -->
                <form class="d-flex search-form" method="GET">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Tìm kiếm sản phẩm..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> 
                    </button>
                </form>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Giỏ hàng
                        </a>
                    </li>
                    <?php if(isset($_SESSION['USER'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> 
                                <?php echo htmlspecialchars($_SESSION['USER']['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="userlogin.php">
                                <i class="fas fa-sign-in-alt"></i> Đăng nhập
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Hiển thị sản phẩm -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php
            try {
                $sql = "SELECT * FROM products ORDER BY id DESC";
                $stmt = $conn->prepare($sql);
                $stmt->execute();
                
                while ($product = $stmt->fetch()) {
                    ?>
                    <div class="col d-flex align-items-stretch">
                        <div class="card w-100">
                            <!-- Hình ảnh có thể click -->
                            <div class="card-img-wrapper" style="height: 200px; overflow: hidden; cursor: pointer;" 
                                 onclick="showProductDetails(
                                    '<?php echo htmlspecialchars($product['name']); ?>', 
                                    '<?php echo number_format($product['price'], 0, ',', '.'); ?>đ',
                                    '<?php echo htmlspecialchars($product['category']); ?>',
                                    '<?php echo htmlspecialchars($product['description']); ?>',
                                    '../images/<?php echo $product['image']; ?>'
                                 )">
                                <img src="../images/<?php echo $product['image']; ?>" 
                                     class="card-img-top h-100 w-100"
                                     alt="<?php echo $product['name']; ?>"
                                     style="object-fit: cover;">
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                               
                                <h5 class="card-title product-title mb-2" style="cursor: pointer;"
                                    onclick="showProductDetails(
                                        '<?php echo htmlspecialchars($product['name']); ?>', 
                                        '<?php echo number_format($product['price'], 0, ',', '.'); ?>đ',
                                        '<?php echo htmlspecialchars($product['category']); ?>',
                                        '<?php echo htmlspecialchars($product['description']); ?>',
                                        '../images/<?php echo $product['image']; ?>'
                                    )">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h5>
                                
                                <div class="mt-auto">
                                    <p class="card-text text-danger fw-bold fs-5 mb-3">
                                        <?php echo number_format($product['price'], 0, ',', '.'); ?>đ
                                    </p>
                                    
                                    <div class="d-grid gap-2">
                                        <?php if(isset($_SESSION['USER'])): ?>
                                            <a href="cart.php?action=add&id=<?php echo $product['id']; ?>" 
                                               class="btn btn-primary">
                                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                            </a>
                                        <?php else: ?>
                                            <a href="userlogin.php" class="btn btn-secondary">
                                                <i class="fas fa-sign-in-alt"></i> Đăng nhập để mua
                                            </a>
                                        <?php endif; ?>
                                    </div>
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

    <style>
       
        
        .product-card {
            border: 1px solid #ddd;
            transition: transform 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .product-img {
            height: 180px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }
        
        .card-title {
            font-size: 0.9rem;
            font-weight: 500;
            height: 20px;
            overflow: hidden;
        }
        
        .card-title:hover {
            color: #6f42c1;
        }
        
        .sold-out-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        
        .modal-content {
            border: none;
            border-radius: 10px;
        }
        
        .modal-header {
            background-color: #6f42c1;
            color: white;
            border-radius: 10px 10px 0 0;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        @media (max-width: 576px) {
            .product-img {
                height: 150px;
            }
            
            .card-body {
                padding: 0.5rem;
            }
            
            .modal-dialog {
                margin: 0.5rem;
            }
        }
    </style>

    <!-- Modal chi tiết sản phẩm -->
    <div class="modal fade" id="productDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chi tiết sản phẩm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <img id="detailProductImage" class="img-fluid" alt="">
                        </div>
                        <div class="col-md-6">
                            <h4 id="detailProductName"></h4>
                            <p class="text-danger fw-bold fs-4" id="detailProductPrice"></p>
                            <p><strong>Danh mục:</strong> <span id="detailProductCategory"></span></p>
                            <div class="mb-3">
                                <strong>Mô tả:</strong>
                                <p id="detailProductDescription" class="mt-2"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
    function showProductDetails(name, price, category, description, image) {
        document.getElementById('detailProductName').textContent = name;
        document.getElementById('detailProductPrice').textContent = price;
        document.getElementById('detailProductCategory').textContent = category;
        document.getElementById('detailProductDescription').textContent = description;
        document.getElementById('detailProductImage').src = image;
        
        new bootstrap.Modal(document.getElementById('productDetailModal')).show();
    }
    </script>

    <style>
    .modal-xl {
        max-width: 90%;
    }

    .table img {
        max-width: 100%;
        height: auto;
    }

    .compare-highlight {
        background-color: #e8f4f8;
    }

    .product-title:hover {
        color: #0d6efd;
        text-decoration: underline;
    }

    .card-img-wrapper:hover img {
        transform: scale(1.05);
        transition: transform 0.3s ease;
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>