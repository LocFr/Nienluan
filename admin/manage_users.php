<?php
session_start();
include '../config.php';

// Kiểm tra quyền admin
if(!isset($_SESSION['USER']) || $_SESSION['USER']['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Xử lý xóa user
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_users.php");
    exit();
}

// Xử lý cấp quyền admin
if(isset($_GET['promote'])) {
    $id = $_GET['promote'];
    $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: manage_users.php");
    exit();
}

// Xử lý thêm user mới
if(isset($_POST['add_user'])) {
    try {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];

        // Kiểm tra username đã tồn tại chưa
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if($stmt->fetchColumn() > 0) {
            echo "<script>
                alert('Tên đăng nhập đã tồn tại!'); 
                window.location.href='manage_users.php';
            </script>";
            exit();
        }

        // Thêm user mới
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$username, $email, $password, $role]);
        
        if($result) {
            echo "<script>
                alert('Thêm tài khoản thành công!'); 
                window.location.href='manage_users.php';
            </script>";
        } else {
            echo "<script>
                alert('Có lỗi xảy ra khi thêm tài khoản!'); 
                window.location.href='manage_users.php';
            </script>";
        }
    } catch(PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        echo "<script>
            alert('Lỗi database: " . addslashes($e->getMessage()) . "'); 
            window.location.href='manage_users.php';
        </script>";
    }
    exit();
}

// Xử lý cập nhật user
if(isset($_POST['edit_user'])) {
    try {
        $id = $_POST['id'];
        $username = $_POST['username']; // Lấy tên đăng nhập mới
        $email = $_POST['email'];
        $role = $_POST['role'];
        
        // Debug
        error_log("Updating user ID: " . $id);
        error_log("New username: " . $username);
        error_log("New email: " . $email);
        error_log("New role: " . $role);
        
        // Cập nhật tên đăng nhập
        if(!empty($_POST['password'])) {
            // Nếu có cập nhật mật khẩu
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, email = ?, role = ?, password = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$username, $email, $role, $password, $id]);
        } else {
            // Nếu không đổi mật khẩu
            $sql = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$username, $email, $role, $id]);
        }
        
        if($result) {
            echo "<script>
                alert('Cập nhật thành công!'); 
                window.location.href='manage_users.php';
            </script>";
        } else {
            echo "<script>
                alert('Có lỗi xảy ra khi cập nhật!'); 
                window.location.href='manage_users.php';
            </script>";
        }
    } catch(PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        echo "<script>
            alert('Lỗi database: " . addslashes($e->getMessage()) . "'); 
            window.location.href='manage_users.php';
        </script>";
    } catch(Exception $e) {
        error_log("General Error: " . $e->getMessage());
        echo "<script>
            alert('Có lỗi xảy ra: " . addslashes($e->getMessage()) . "'); 
            window.location.href='manage_users.php';
        </script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý tài khoản - XLight</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
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
                        <a class="nav-link" href="manage_products.php">Quản lý sản phẩm</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_orders.php">Quản lý đơn hàng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_users.php">Quản lý người dùng</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['USER'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['USER']['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="../user/profile.php">Thông tin tài khoản</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="user/userlogin.php">Đăng xuất</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Quản lý tài khoản</h2>
            <div>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus"></i> Thêm tài khoản
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên đăng nhập</th>
                        <th>Email</th>
                        <th>Quyền</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->query("SELECT * FROM users ORDER BY id DESC");
                    $users = $stmt->fetchAll();
                    foreach($users as $row):
                    ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['username']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td>
                            <span class="badge <?php echo $row['role'] == 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                <?php echo $row['role']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if($row['role'] != 'admin'): ?>
                                <button class="btn btn-primary btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editUserModal<?php echo $row['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?promote=<?php echo $row['id']; ?>" 
                                   class="btn btn-success btn-sm"
                                   onclick="return confirm('Bạn có chắc muốn cấp quyền admin?')">
                                    <i class="fas fa-user-shield"></i>
                                </a>
                                <a href="?delete=<?php echo $row['id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Bạn có chắc muốn xóa người dùng này?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

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
        .container.mt-5 {
            margin-top: 100px !important;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Modal thêm user -->
    <div class="modal fade" id="addUserModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Thêm tài khoản mới</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên đăng nhập</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quyền</label>
                            <select name="role" class="form-control" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Thêm tài khoản</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal chỉnh sửa user - đặt ngay sau bảng -->
    <?php foreach($users as $user): ?>
    <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">Chỉnh sửa tài khoản</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tên đăng nhập</label>
                            <input type="text" name="username" class="form-control" value="<?php echo $user['username']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo $user['email']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu mới (để trống nếu không đổi)</label>
                            <input type="password" name="password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quyền</label>
                            <select name="role" class="form-control" required>
                                <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">Lưu thay đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</body>
</html>