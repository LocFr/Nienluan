<?php
session_start();
include '../config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['USER'])) {
    header("Location: userlogin.php");
    exit();
}

// Kiểm tra order_id
if (!isset($_GET['order_id'])) {
    header("Location: donhang.php");
    exit();
}

$order_id = $_GET['order_id'];

try {
    // Lấy thông tin đơn hàng
    $sql = "SELECT * FROM orders WHERE id = ? AND customer_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$order_id, $_SESSION['USER']['username']]);
    $order = $stmt->fetch();

    if (!$order) {
        header("Location: donhang.php");
        exit();
    }

    // Lấy chi tiết đơn hàng
    $sql = "SELECT od.*, p.name as product_name 
            FROM order_details od 
            JOIN products p ON od.product_id = p.id 
            WHERE od.order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$order_id]);
    $details = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log("Lỗi SQL: " . $e->getMessage());
    header("Location: donhang.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hóa đơn #<?php echo $order_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3>Hóa đơn #<?php echo $order_id; ?></h3>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h6 class="mb-3">Thông tin khách hàng:</h6>
                        <div>Tên: <?php echo $order['customer_name']; ?></div>
                        <div>SĐT: <?php echo $order['phone']; ?></div>
                        <div>Địa chỉ: <?php echo $order['address']; ?></div>
                    </div>
                    <div class="col-sm-6">
                        <h6 class="mb-3">Thông tin đơn hàng:</h6>
                        <div>Mã đơn: #<?php echo $order_id; ?></div>
                        <div>Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></div>
                        <div>Trạng thái: <?php echo $order['status']; ?></div>
                    </div>
                </div>

                <div class="table-responsive-sm">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-end">Giá</th>
                                <th class="text-center">Số lượng</th>
                                <th class="text-end">Tổng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($details as $item): ?>
                            <tr>
                                <td><?php echo $item['product_name']; ?></td>
                                <td class="text-end"><?php echo number_format($item['price']); ?>đ</td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-end"><?php echo number_format($item['price'] * $item['quantity']); ?>đ</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                                <td class="text-end"><strong><?php echo number_format($order['total_amount']); ?>đ</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="donhang.php" class="btn btn-primary">Quay lại đơn hàng</a>
                <button onclick="window.print()" class="btn btn-success">In hóa đơn</button>
            </div>
        </div>
    </div>
</body>
</html>