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
        $_SESSION['cart'][$id] = 1; // Set số lượng = 1
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
                'image' => $product['image']
            ];
            $total += $product['price']; // Bỏ nhân với số lượng
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
                1, // Set số lượng = 1
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

<!-- Phần HTML - Bảng giỏ hàng đã sửa -->
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Ảnh</th>
                <th>Tên SP</th>
                <th>Giá</th>
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
                <td class="align-middle">
                    <?php echo number_format($product['price']); ?>đ
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
                <td colspan="3" class="text-end"><strong>Tổng cộng:</strong></td>
                <td><strong><?php echo number_format($total); ?>đ</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>        
</div> 