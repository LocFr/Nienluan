<?php
session_start();
include '../config.php';

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

// Thêm phần xử lý thanh toán sau phần xử lý giỏ hàng
if(isset($_POST['checkout']) && !empty($_SESSION['cart'])) {
    try {
        // Kiểm tra đăng nhập
        if(!isset($_SESSION['USER'])) {
            $_SESSION['error'] = "Vui lòng đăng nhập để thanh toán";
            header("Location: userlogin.php");
            exit();
        }

        $user_id = $_SESSION['USER']['id'];
        $order_date = date('Y-m-d H:i:s');
        $shipping_address = $_POST['shipping_address']; // Thêm địa chỉ giao hàng
        $phone = $_POST['phone']; // Thêm số điện thoại
        $payment_method = $_POST['payment_method']; // Thêm phương thức thanh toán

        // Tạo đơn hàng mới
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, order_date, status, shipping_address, phone, payment_method) 
                               VALUES (?, ?, ?, 'pending', ?, ?, ?)");
        $stmt->execute([$user_id, $total, $order_date, $shipping_address, $phone, $payment_method]);
        $order_id = $conn->lastInsertId();

        // Thêm chi tiết đơn hàng
        foreach($_SESSION['cart'] as $product_id => $quantity) {
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if($product) {
                // Kiểm tra số lượng tồn kho
                if($product['quantity'] < $quantity) {
                    throw new Exception("Sản phẩm {$product['name']} chỉ còn {$product['quantity']} sản phẩm");
                }

                // Thêm vào order_details
                $stmt = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $product_id, $quantity, $product['price']]);

                // Cập nhật số lượng sản phẩm
                $stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                $stmt->execute([$quantity, $product_id]);
            }
        }

        // Xóa giỏ hàng
        unset($_SESSION['cart']);

        $_SESSION['success'] = "Đặt hàng thành công! Mã đơn hàng của bạn là #" . $order_id;
        header("Location: order_success.php?order_id=" . $order_id);
        exit();

    } catch(Exception $e) {
        $_SESSION['error'] = "Lỗi khi đặt hàng: " . $e->getMessage();
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
        :root {
            --primary-color: #6f42c1;
            --secondary-color: #8a5cd0;
        }
        
        body {
            background-image: url('../images/background.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(to right, #6f42c1, #6610f2);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 24px;
        }
        
        .cart-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(111, 66, 193, 0.3);
            backdrop-filter: blur(10px);
            padding: 20px;
            margin-top: 30px;
        }
        
        .table {
            background-color: rgba(255, 255, 255, 0.8);
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
        }
        
        .btn-success:hover {
            background: linear-gradient(45deg, #20c997, #28a745);
            transform: translateY(-2px);
        }
        
        .product-img {
            max-width: 80px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .quantity-input {
            width: 80px;
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 5px;
        }
        
        .total-row {
            font-size: 1.2em;
            font-weight: bold;
            background-color: rgba(111, 66, 193, 0.1);
        }
    </style>
</head>
<body>
    <header>
        <div class="header-top bg-dark text-white py-2">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 col-md-12">
                        <span><i class="fas fa-phone-alt me-2"></i>0123 456 789</span>
                        <span class="ms-4"><i class="fas fa-envelope me-2"></i>contact@xlight.com</span>
                    </div>
                    <div class="col-lg-6 col-md-12 text-end">
                        <?php if(isset($_SESSION['USER'])): ?>
                            <a href="userlogin.php?reqact=userlogout" class="text-white text-decoration-none">
                                <i class="fas fa-sign-out-alt me-2"></i>Đăng xuất
                            </a>
                        <?php else: ?>
                            <a href="userlogin.php" class="text-white text-decoration-none">
                                <i class="fas fa-user me-2"></i>Đăng nhập
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="header-main py-3 bg-white">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-3 col-md-12 text-center text-lg-start mb-3 mb-lg-0">
                        <a href="../index.php" class="text-decoration-none">
                            <img src="../images/logo.png" alt="XLight Logo" style="height: 60px;">
                        </a>
                    </div>
                    <div class="col-lg-6 col-md-12 mb-3 mb-lg-0">
                        <form action="../search.php" method="GET" class="d-flex">
                            <input type="text" name="keyword" class="form-control me-2" placeholder="Tìm kiếm sản phẩm...">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-lg-3 col-md-12 text-center text-lg-end">
                        <a href="cart.php" class="btn btn-outline-primary position-relative">
                            <i class="fas fa-shopping-cart me-2"></i>Giỏ hàng
                            <?php if(!empty($_SESSION['cart'])): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo count($_SESSION['cart']); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="../index.php"><i class="fas fa-home me-2"></i>Trang chủ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../user/customer.php"><i class="fas fa-lightbulb me-2"></i>Sản phẩm</a>
                      
                        </li>
                    </ul>
                    <?php if(isset($_SESSION['USER']) && $_SESSION['USER']['role'] == 'admin'): ?>
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/index.php"><i class="fas fa-user-cog me-2"></i>Admin</a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <div class="container mt-4">
        <h2>Giỏ hàng</h2>
        <a href="../index.php" class="btn btn-primary mb-3">Tiếp tục mua hàng</a>

        <?php if(empty($cart_products)): ?>
            <div class="alert alert-info">Giỏ hàng trống</div>
        <?php else: ?>
            <form method="post">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Giá</th>
                            <th>Số lượng</th>
                            <th>Tổng</th>
                            <th>Xóa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cart_products as $item): ?>
                            <tr>
                                <td>
                                    <img src="../images/<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="product-img me-2">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </td>
                                <td><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
                                <td>
                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="0" class="form-control quantity-input">
                                </td>
                                <td><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</td>
                                <td>
                                    <a href="cart.php?action=remove&id=<?php echo $item['id']; ?>" 
                                       class="btn btn-danger btn-sm">Xóa</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="text-end">
                    <strong>Tổng cộng:</strong> <?php echo number_format($total, 0, ',', '.'); ?>đ
                </div>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="submit" name="update" class="btn btn-outline-primary">
                        <i class="fas fa-sync-alt me-2"></i>Cập nhật
                    </button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                        <i class="fas fa-check me-2"></i>Thanh toán
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <!-- Thêm modal thanh toán -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-purple text-white">
                    <h5 class="modal-title">Thông tin thanh toán</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="process_order.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Họ và tên</label>
                            <input type="text" name="fullname" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ giao hàng</label>
                            <textarea name="address" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phương thức thanh toán</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="cod">Thanh toán khi giao hàng (COD)</option>
                                <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Đặt hàng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>