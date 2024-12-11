<?php
session_start();
include '../config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['USER']) || $_SESSION['USER']['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Xử lý xóa đơn hàng
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
        $conn->beginTransaction();
        
        // Xóa chi tiết đơn hàng trước
        $sql = "DELETE FROM order_details WHERE order_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_GET['id']]);
        
        // Sau đó xóa đơn hàng
        $sql = "DELETE FROM orders WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_GET['id']]);
        
        $conn->commit();
        echo "<script>alert('Xóa đơn hàng thành công!');</script>";
    } catch(PDOException $e) {
        $conn->rollBack();
        echo "<script>alert('Lỗi khi xóa đơn hàng!');</script>";
    }
}

// Xử lý cập nhật trạng thái
if (isset($_POST['update_status'])) {
    try {
        $sql = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$_POST['status'], $_POST['order_id']]);
        echo "<script>alert('Cập nhật trạng thái thành công!');</script>";
    } catch(PDOException $e) {
        echo "<script>alert('Lỗi khi cập nhật trạng thái!');</script>";
    }
}

// Xử lý sửa đơn hàng
if (isset($_POST['edit_order'])) {
    try {
        $sql = "UPDATE orders SET 
                customer_name = ?,
                phone = ?,
                address = ?
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_POST['customer_name'],
            $_POST['phone'], 
            $_POST['address'],
            $_POST['order_id']
        ]);
        echo "<script>alert('Cập nhật đơn hàng thành công!');</script>";
    } catch(PDOException $e) {
        echo "<script>alert('Lỗi khi cập nhật đơn hàng!');</script>";
    }
}

// Lấy danh sách đơn hàng
$sql = "SELECT * FROM orders ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng - XLight</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table td, .table th {
            white-space: nowrap;
            vertical-align: middle;
        }
        
        .table td:nth-child(4) {  /* Cột địa chỉ */
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .btn-group {
            display: flex;
            gap: 0.25rem;
        }
        
        .modal-dialog {
            max-width: 90%;
            margin: 1.75rem auto;
        }
        
        @media (min-width: 768px) {
            .modal-dialog {
                max-width: 700px;
            }
        }
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
    <nav class="navbar navbar-expand-lg navbar-light" style="background-color: #e6e6fa;">
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
                        <a class="nav-link" href="../user/customer.php">Hàng hóa</a>
                    </li>
                    <?php if(isset($_SESSION['USER'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../user/cart.php">
                                <i class="fas fa-shopping-cart"></i> Giỏ hàng
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../user/donhang.php">
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
                                        <a class="dropdown-item" href="manage_products.php">
                                            <i class="fas fa-box"></i> Quản lý hàng hóa
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="manage_orders.php">
                                            <i class="fas fa-file-invoice"></i> Quản lý đơn hàng
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container-fluid px-4 mt-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white py-3">
                <h4 class="mb-0">
                    <i class="fas fa-file-invoice me-2"></i>
                    Quản lý đơn hàng
                </h4>
            </div>
            <div class="card-body px-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-3">Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>SĐT</th>
                                <th>Địa chỉ</th>
                                <th>Tổng tiền</th>
                                <th>Ngày đặt</th>
                                <th>Trạng thái</th>
                                <th class="text-center">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                            <tr>
                                <td class="fw-bold px-3">#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['phone']); ?></td>
                                <td class="text-truncate" style="max-width: 200px;">
                                    <?php echo htmlspecialchars($order['address']); ?>
                                </td>
                                <td class="fw-bold text-danger">
                                    <?php echo number_format($order['total_amount']); ?>đ
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="form-select form-select-sm w-auto" 
                                                onchange="this.form.submit()"
                                                style="max-width: 140px;">
                                            <option value="Đang xử lý" 
                                                <?php echo $order['status'] == 'Đang xử lý' ? 'selected' : ''; ?>>
                                                🕒 Đang xử lý
                                            </option>
                                            <option value="Đã xác nhận"
                                                <?php echo $order['status'] == 'Đã xác nhận' ? 'selected' : ''; ?>>
                                                ✅ Đã xác nhận
                                            </option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-outline-warning btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?php echo $order['id']; ?>"
                                                title="Sửa đơn hàng">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-outline-info btn-sm" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detailModal<?php echo $order['id']; ?>"
                                                title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <a href="?action=delete&id=<?php echo $order['id']; ?>" 
                                           class="btn btn-outline-danger btn-sm"
                                           onclick="return confirm('Bạn có chắc muốn xóa đơn hàng này không?')"
                                           title="Xóa đơn hàng">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <?php foreach($orders as $order): ?>
        <!-- Modal chi tiết đơn hàng -->
        <div class="modal fade" id="detailModal<?php echo $order['id']; ?>">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-info-circle me-2"></i>
                            Chi tiết đơn hàng #<?php echo $order['id']; ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-user me-2"></i>Khách hàng:</strong> 
                                   <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                <p><strong><i class="fas fa-phone me-2"></i>SĐT:</strong> 
                                   <?php echo htmlspecialchars($order['phone']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-map-marker-alt me-2"></i>Địa chỉ:</strong> 
                                   <?php echo htmlspecialchars($order['address']); ?></p>
                                <p><strong><i class="fas fa-calendar me-2"></i>Ngày đặt:</strong> 
                                   <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                            </div>
                        </div>
                        <table class="table table-bordered table-striped">
                            <?php
                            $sql = "SELECT od.*, p.name as product_name 
                                    FROM order_details od 
                                    JOIN products p ON od.product_id = p.id 
                                    WHERE od.order_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->execute([$order['id']]);
                            $details = $stmt->fetchAll();
                            ?>
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Đơn giá</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($details as $detail): ?>
                                <tr>
                                    <td><?php echo $detail['product_name']; ?></td>
                                    <td><?php echo $detail['quantity']; ?></td>
                                    <td><?php echo number_format($detail['price']); ?>đ</td>
                                    <td><?php echo number_format($detail['price'] * $detail['quantity']); ?>đ</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal sửa đơn hàng -->
        <div class="modal fade" id="editModal<?php echo $order['id']; ?>">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>
                            Sửa đơn hàng #<?php echo $order['id']; ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-user me-2"></i>Tên khách hàng
                                </label>
                                <input type="text" class="form-control" name="customer_name" 
                                       value="<?php echo htmlspecialchars($order['customer_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-phone me-2"></i>Số điện thoại
                                </label>
                                <input type="text" class="form-control" name="phone" 
                                       value="<?php echo htmlspecialchars($order['phone']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="fas fa-map-marker-alt me-2"></i>Địa chỉ
                                </label>
                                <textarea class="form-control" name="address" rows="3" required><?php echo htmlspecialchars($order['address']); ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Đóng
                            </button>
                            <button type="submit" name="edit_order" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Lưu thay đổi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 