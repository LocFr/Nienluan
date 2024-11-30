<?php
session_start();
if (!isset($_SESSION['USER']) && !isset($_SESSION['ADMIN'])) {
    header('Location: ../index.php');
    exit();
}

include '../config.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả tìm kiếm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Sử dụng lại style từ customer.php -->
    <style>
        /* Copy toàn bộ CSS từ customer.php vào đây */
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="m-0">Kết quả tìm kiếm: "<?php echo htmlspecialchars($keyword); ?>"</h2>
                <a href="../index.php" class="btn btn-outline-primary btn-home">
                    <i class="fas fa-home"></i> Trang chủ
                </a>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="row g-4">
            <?php
            if($keyword) {
                try {
                    $sql = "SELECT * FROM products WHERE 
                            (name LIKE ? OR description LIKE ?) 
                            AND quantity > 0 
                            ORDER BY id DESC";
                    $stmt = $conn->prepare($sql);
                    $searchTerm = "%{$keyword}%";
                    $stmt->execute([$searchTerm, $searchTerm]);
                    
                    if ($stmt->rowCount() > 0) {
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            // Sử dụng lại template hiển thị sản phẩm từ customer.php
                            ?>
                            <div class="col-md-3 fade-in">
                                <div class="product-card">
                                    <!-- Copy phần hiển thị sản phẩm từ customer.php -->
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<div class='col-12'>
                                <div class='alert alert-info text-center'>
                                    <i class='fas fa-info-circle me-2'></i>
                                    Không tìm thấy sản phẩm nào phù hợp.
                                </div>
                              </div>";
                    }
                } catch(PDOException $e) {
                    echo "<div class='col-12'>
                            <div class='alert alert-danger text-center'>
                                <i class='fas fa-exclamation-circle me-2'></i>
                                Lỗi: " . $e->getMessage() . "
                            </div>
                          </div>";
                }
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 