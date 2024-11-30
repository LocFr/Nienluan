<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý hàng hóa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quản lý hàng hóa</h2>
            <a href="../index.php" class="btn btn-secondary">
                Quay lại trang chủ
            </a>
        </div>
        
        <!-- Form thêm sản phẩm -->
        <div class="card mb-4">
            <div class="card-body">
                <h5>Thêm sản phẩm mới</h5>
                <form action="?action=add" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <input type="text" name="name" class="form-control" placeholder="Tên sản phẩm" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <input type="number" name="price" class="form-control" placeholder="Giá" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <input type="number" name="quantity" class="form-control" placeholder="Số lượng" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <input type="file" name="image" class="form-control" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <button type="submit" class="btn btn-primary w-100">Thêm</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danh sách sản phẩm -->
        <div class="card">
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tên sản phẩm</th>
                            <th>Giá</th>
                            <th>Số lượng</th>
                            <th>Ảnh</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo number_format($product['price'], 0, ',', '.'); ?>đ</td>
                            <td><?php echo $product['quantity']; ?></td>
                            <td>
                                <img src="../images/<?php echo $product['image']; ?>" 
                                     alt="<?php echo $product['name']; ?>"
                                     style="height: 50px;">
                            </td>
                            <td>
                                <a href="?action=edit&id=<?php echo $product['id']; ?>" 
                                   class="btn btn-warning btn-sm">Sửa</a>
                                <a href="?action=delete&id=<?php echo $product['id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Form sửa sản phẩm -->
        <?php if(isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])): ?>
            <?php
            $edit_id = $_GET['id'];
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$edit_id]);
            $edit_product = $stmt->fetch();
            ?>
            <div class="card mt-4">
                <div class="card-body">
                    <h5>Sửa sản phẩm</h5>
                    <form action="?action=update" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <input type="text" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($edit_product['name']); ?>" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <input type="number" name="price" class="form-control" 
                                       value="<?php echo $edit_product['price']; ?>" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <input type="number" name="quantity" class="form-control" 
                                       value="<?php echo $edit_product['quantity']; ?>" required>
                            </div>
                            <div class="col-md-2 mb-3">
                                <input type="file" name="image" class="form-control">
                                <small class="text-muted">Để trống nếu không đổi ảnh</small>
                            </div>
                            <div class="col-md-2 mb-3">
                                <button type="submit" class="btn btn-warning w-100">Cập nhật</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>