<?php
session_start();
include '../config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['USER'])) {
    header("Location: userlogin.php");
    exit();
}

// Khởi tạo giỏ hàng
if(!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Thêm vào giỏ hàng
if(isset($_GET['action']) && $_GET['action'] == 'add') {
    $id = intval($_GET['id']);
    // Kiểm tra sản phẩm có tồn tại
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    if($stmt->fetch()) {
        if(isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]++;
        } else {
            $_SESSION['cart'][$id] = 1;
        }
    }
    header("Location: cart.php");
    exit();
}

// Xóa khỏi giỏ hàng
if(isset($_GET['action']) && $_GET['action'] == 'remove') {
    $id = intval($_GET['id']);
    unset($_SESSION['cart'][$id]);
    header("Location: cart.php");
    exit();
}

// Cập nhật số lượng
if(isset($_POST['update'])) {
    foreach($_POST['quantity'] as $id => $qty) {
        $id = intval($id);
        $qty = intval($qty);
        if($qty > 0) {
            $_SESSION['cart'][$id] = $qty;
        } else {
            unset($_SESSION['cart'][$id]);
        }
    }
    header("Location: cart.php");
    exit();
}

// Lấy thông tin sản phẩm trong giỏ hàng
$cart_products = [];
$total = 0;

if(!empty($_SESSION['cart'])) {
    foreach($_SESSION['cart'] as $id => $qty) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($product) {
            $cart_products[] = [
                'id' => $id,
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $qty
            ];
            $total += $product['price'] * $qty;
        } else {
            // Xóa sản phẩm không tồn tại
            unset($_SESSION['cart'][$id]);
        }
    }
}

// Xử lý thanh toán
if(isset($_POST['checkout'])) {
    try {
        // Kiểm tra đăng nhập
        if(!isset($_SESSION['USER'])) {
            echo "<script>alert('Vui lòng đăng nhập để thanh toán!'); window.location='userlogin.php';</script>";
            exit();
        }

        // Kiểm tra giỏ hàng có trống không
        if(empty($_SESSION['cart'])) {
            echo "<script>alert('Giỏ hàng trống!'); window.location='cart.php';</script>";
            exit();
        }

        // Kiểm tra dữ liệu POST
        if(empty($_POST['phone']) || empty($_POST['shipping_address'])) {
            echo "<script>alert('Vui lòng điền đầy đủ thông tin!'); window.location='cart.php';</script>";
            exit();
        }

        // Bắt đầu transaction
        $conn->beginTransaction();

        // Thêm vào bảng orders
        $sql = "INSERT INTO orders (customer_name, phone, address, total_amount, status, created_at) 
                VALUES (?, ?, ?, ?, 'Đã xác nhận', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_SESSION['USER']['username'],
            $_POST['phone'],
            $_POST['shipping_address'],
            $total
        ]);
        
        $order_id = $conn->lastInsertId();

        // Thêm chi tiết đơn hàng
        foreach($cart_products as $product) {
            $sql = "INSERT INTO order_details (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $order_id,
                $product['id'],
                $product['quantity'],
                $product['price']
            ]);
        }

        // Commit transaction
        $conn->commit();
        
        // Xóa giỏ hàng
        unset($_SESSION['cart']);
        
        // Chuyển hướng đến trang hóa đơn
        header("Location: hoadon.php?order_id=" . $order_id);
        exit();

    } catch(PDOException $e) {
        // Rollback nếu có lỗi
        $conn->rollBack();
        echo "<script>alert('Có lỗi xảy ra: " . $e->getMessage() . "'); window.location='cart.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giỏ hàng - XLight</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
       .navbar {
            background: #e6e6fa !important;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
        }
        .navbar-brand {
            font-weight: bold;
            color: #6a5acd !important;
        }
        .nav-link {
            color: #6a5acd !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: #483d8b !important;
            transform: translateY(-2px);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }
        .status-processing {
            background-color: #ffc107;
            color: #000;
        }
        .status-confirmed {
            background-color: #28a745;
            color: #fff;
        }
        .main-content {
            margin-top: 56px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #6f42c1;">
            <div class="container">
                <a class="navbar-brand" href="../index.php">
                    <img src="../images/logo.png" alt="XLight Logo" height="50">
                    XLight
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                      
                            <a class="nav-link" href="customer.php">Hàng hóa</a>
                        </li>
                        <?php if(isset($_SESSION['USER'])): ?>
                            
                            <li class="nav-item">
                                <a class="nav-link" href="donhang.php">
                                    <i class="fas fa-file-invoice"></i> Đơn hàng
                                </a>
                            </li>
                            <?php if(isset($_SESSION['USER']) && $_SESSION['USER']['role'] == 'admin'): ?>
                       
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav">
                        <?php if(isset($_SESSION['USER'])): ?>
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

        <!-- Thanh tìm kiếm -->
        <div class="container mt-3">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form action="../search.php" method="GET" class="d-flex">
                        <input type="text" name="keyword" class="form-control me-2" 
                               placeholder="Tìm kiếm sản phẩm..." required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="container mt-4">
        <h2 class="mb-4">Giỏ hàng của bạn</h2>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Ảnh</th>
                        <th>Tên SP</th>
                        <th>Giá</th>
                        <th>SL</th>
                        <th>Tổng</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($_SESSION['cart'] as $id => $quantity): 
                        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
                        $stmt->execute([$id]);
                        $product = $stmt->fetch();
                        if($product):
                    ?>
                    <tr>
                        <td>
                            <img src="../images/<?php echo $product['image']; ?>" 
                                 class="cart-img" alt="<?php echo $product['name']; ?>">
                        </td>
                        <td class="align-middle"><?php echo $product['name']; ?></td>
                        <td class="align-middle"><?php echo number_format($product['price']); ?>đ</td>
                        <td class="align-middle" style="width: 120px;">
                            <div class="input-group">
                                <a href="?action=decrease&id=<?php echo $id; ?>" 
                                   class="btn btn-sm btn-outline-secondary">-</a>
                                <input type="text" class="form-control text-center" 
                                       value="<?php echo $quantity; ?>" readonly>
                                <a href="?action=increase&id=<?php echo $id; ?>" 
                                   class="btn btn-sm btn-outline-secondary">+</a>
                            </div>
                        </td>
                        <td class="align-middle">
                            <?php echo number_format($product['price'] * $quantity); ?>đ
                        </td>
                        <td class="align-middle">
                            <a href="?action=remove&id=<?php echo $id; ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Xóa sản phẩm này?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endif; endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-active">
                        <td colspan="4" class="text-end"><strong>Tổng cộng:</strong></td>
                        <td><strong><?php echo number_format($total); ?>đ</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex justify-content-between mt-3">
            <a href="customer.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Tiếp tục mua hàng
            </a>
            <?php if(!empty($_SESSION['cart'])): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                    Thanh toán<i class="fas fa-arrow-right ms-2"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .cart-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .table th {
            background-color: #6f42c1;
            color: white;
            font-weight: 500;
        }
        
        .input-group {
            width: 120px;
        }
        
        .input-group input {
            height: 31px;
            font-size: 14px;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        @media (max-width: 768px) {
            .table {
                font-size: 14px;
            }
            
            .cart-img {
                width: 40px;
                height: 40px;
            }
            
            .input-group {
                width: 100px;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="cart.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Thông tin thanh toán</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ giao hàng</label>
                            <textarea name="shipping_address" class="form-control" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" name="checkout" class="btn btn-primary">Xác nhận đặt hàng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
  