<?php
session_start();
include '../config.php';

// Lấy tham số sắp xếp từ URL
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

try {
    // Xây dựng câu truy vấn SQL dựa trên điều kiện sắp xếp
    $sql = "SELECT * FROM products WHERE 1=1";
    
    // Thêm điều kiện tìm kiếm nếu có
    if ($search) {
        $sql .= " AND name LIKE :search";
    }
    
    // Thêm điều kiện sắp xếp
    switch($sort) {
        case 'name_asc':
            $sql .= " ORDER BY name ASC";
            break;
        case 'name_desc':
            $sql .= " ORDER BY name DESC";
            break;
        case 'price_asc':
            $sql .= " ORDER BY price ASC";
            break;
        case 'price_desc':
            $sql .= " ORDER BY price DESC";
            break;
        default:
            $sql .= " ORDER BY id DESC";
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
            color: #6a5acd !important; /* Màu tím đậm khi hover */
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
            color: #0d6efd !important; /* Màu xanh của Bootstrap */
        }
        .dropdown-item:hover {
            color: #0a58ca !important; /* Màu xanh đậm hơn khi hover */
            background-color: #f8f9fa;
        }
        .navbar {
            background: #e6e6fa !important;
        }
        body {
            padding-top: 95px;
        }
        .nav-link {
            color: #e6e6fa !important;
        }
        .navbar-brand {
            color: #e6e6fa !important;
        }
        .dropdown-item {
            color: #e6e6fa !important;
        }
        .nav-link:hover, 
        .navbar-brand:hover,
        .dropdown-item:hover {
            color: #c8c8ff !important;
        }
        /* Style cho thanh tìm kiếm */
        .input-group {
            width: 300px;
        }
        .input-group .form-control {
            border-right: none;
        }
        .input-group .btn {
            border-left: none;
            background-color: white;
        }
        .input-group .btn:hover {
            background-color: #f8f9fa;
        }
        /* Responsive */
        @media (max-width: 992px) {
            .input-group {
                width: 100%;
                margin: 10px 0;
            }
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
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <form class="d-flex me-3" method="GET">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Tìm kiếm sản phẩm..." 
                               value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button class="btn btn-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
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

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Hiển thị kết quả tìm kiếm nếu có -->
        <?php if(!empty($search)): ?>
            <?php if(empty($products)): ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Không có sản phẩm nào tên "<?php echo htmlspecialchars($search); ?>", xin thử lại!
                    <div class="mt-2">
                        <a href="customer.php" class="btn btn-outline-warning">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-search me-2"></i>
                    Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($search); ?>"
                    <a href="customer.php" class="float-end text-decoration-none">
                        <i class="fas fa-times"></i> Xóa tìm kiếm
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Dropdown sắp xếp -->
        <div class="d-flex justify-content-end mb-3">
            <select class="form-select w-auto" onchange="window.location.href=this.value">
                <option value="?sort=" <?php echo empty($sort) ? 'selected' : ''; ?>>
                    Sắp xếp theo
                </option>
                <option value="?sort=name_asc" <?php echo ($sort ?? '') == 'name_asc' ? 'selected' : ''; ?>>
                    Tên A-Z
                </option>
                <option value="?sort=name_desc" <?php echo ($sort ?? '') == 'name_desc' ? 'selected' : ''; ?>>
                    Tên Z-A
                </option>
                <option value="?sort=price_asc" <?php echo ($sort ?? '') == 'price_asc' ? 'selected' : ''; ?>>
                    Giá thấp đến cao
                </option>
                <option value="?sort=price_desc" <?php echo ($sort ?? '') == 'price_desc' ? 'selected' : ''; ?>>
                    Giá cao đến thấp
                </option>
            </select>
        </div>

        <!-- Hiển thị sản phẩm -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>