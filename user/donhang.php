<?php
session_start();
include '../config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['USER'])) {
    header("Location: userlogin.php");
    exit();
}

// Lấy danh sách đơn hàng
$sql = "SELECT * FROM orders WHERE customer_name = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$_SESSION['USER']['username']]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đơn hàng của tôi - XLight</title>
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
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
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
                        <a class="nav-link" href="customer.php">Hàng hóa</a>
                    </li>
                    <?php if(isset($_SESSION['USER'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart"></i> Giỏ hàng
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h2 class="text-center mb-4">Đơn hàng của tôi</h2>
            
            <?php if(empty($orders)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>Bạn chưa có đơn hàng nào.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn</th>
                                <th>Ngày đặt</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td><?php echo number_format($order['total_amount']); ?>đ</td>
                                <td>
                                    <span class="status-badge <?php echo $order['status'] == 'Đang xử lý' ? 'status-processing' : 'status-confirmed'; ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modal<?php echo $order['id']; ?>">
                                        <i class="fas fa-eye me-1"></i> Chi tiết
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modals -->
                <?php foreach($orders as $order): ?>
                <div class="modal fade" id="modal<?php echo $order['id']; ?>">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Chi tiết đơn #<?php echo $order['id']; ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Người nhận:</strong> <?php echo $order['customer_name']; ?></p>
                                <p><strong>Số điện thoại:</strong> <?php echo $order['phone']; ?></p>
                                <p><strong>Địa chỉ:</strong> <?php echo $order['address']; ?></p>
                                <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_amount']); ?>đ</p>
                                <p><strong>Trạng thái:</strong> <?php echo $order['status']; ?></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                <form action="hoadon.php" method="GET" class="d-inline">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" class="btn btn-primary">Xem hóa đơn</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>