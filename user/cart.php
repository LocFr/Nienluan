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
            header("Location: userlogin.php");
            exit();
        }

        // Kiểm tra giỏ hàng có trống không
        if(empty($_SESSION['cart'])) {
            echo "<script>alert('Giỏ hàng trống!');</script>";
            echo "<script>window.location='cart.php';</script>";
            exit();
        }

        // Kiểm tra dữ liệu POST
        if(empty($_POST['phone']) || empty($_POST['shipping_address'])) {
            echo "<script>alert('Vui lòng điền đầy đủ thông tin!');</script>";
            echo "<script>window.location='cart.php';</script>";
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
        error_log("Lỗi SQL: " . $e->getMessage());
        echo "<script>alert('Có lỗi xảy ra khi xử lý đơn hàng!');</script>";
        echo "<script>window.location='cart.php';</script>";
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
                        <li class="nav-item">
                            <a class="nav-link" href="../index.php"><i class="fas fa-home me-2"></i>Trang chủ</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="customer.php">Hàng hóa</a>
                        </li>
                        <?php if(isset($_SESSION['USER'])): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="cart.php">
                                    <i class="fas fa-shopping-cart"></i> Giỏ hàng
                                    <?php if(!empty($_SESSION['cart'])): ?>
                                        <span class="badge bg-danger"><?php echo count($_SESSION['cart']); ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="donhang.php">
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

            <!-- Modal Thanh toán -->
            <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="checkoutModalLabel">Thông tin giao hàng</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Số điện thoại</label>
                                    <input type="text" class="form-control" id="phone" name="phone" required>
                                </div>
                                <div class="mb-3">
                                    <label for="shipping_address" class="form-label">Địa chỉ giao hàng</label>
                                    <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <p class="mb-0"><strong>Tổng tiền:</strong> <?php echo number_format($total); ?>đ</p>
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

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Kiểm tra xem có lỗi modal không hiển thị
                var checkoutBtn = document.querySelector('[data-bs-target="#checkoutModal"]');
                checkoutBtn.addEventListener('click', function() {
                    var modal = new bootstrap.Modal(document.getElementById('checkoutModal'));
                    modal.show();
                });
            });
            </script>
        <?php endif; ?>
    </div>

</body>
</html>
  