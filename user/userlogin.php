<?php
session_start();
include '../config.php';

// Xử lý đăng ký
if(isset($_POST['userregister'])) {
    try {
        $username = $_POST['reg_username'];
        $password = $_POST['reg_password'];
        $email = $_POST['email'];
        
        // Kiểm tra email đã tồn tại chưa
        $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $check->execute([$email]);
        if($check->rowCount() > 0) {
            $_SESSION['error'] = "Email này đã được sử dụng!";
            header("Location: userlogin.php");
            exit();
        }

        // Kiểm tra username đã tồn tại chưa
        $check = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $check->execute([$username]);
        if($check->rowCount() > 0) {
            $_SESSION['error'] = "Tên đăng nhập này đã tồn tại!";
            header("Location: userlogin.php");
            exit();
        }

        $role = (strpos($email, 'admin@xlight.com') !== false) ? 'admin' : 'user';
        
        $sql = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if($stmt->execute([$username, $password, $email, $role])) {
            $_SESSION['USER'] = [
                'username' => $username,
                'role' => $role
            ];
            header("Location: ../index.php");
            exit();
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Có lỗi xảy ra: " . $e->getMessage();
        header("Location: userlogin.php");
        exit();
    }
}

// Xử lý đăng nhập
if(isset($_POST['userlogin'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if($user) {
        $_SESSION['USER'] = [
            'username' => $user['username'],
            'role' => $user['role']
        ];
        header("Location: ../index.php");
        exit();
    } else {
        $_SESSION['error'] = "Sai tên đăng nhập hoặc mật khẩu!";
    }
}

// Xử lý đăng xuất
if(isset($_GET['reqact']) && $_GET['reqact'] == 'userlogout') {
    unset($_SESSION['USER']);
    header("Location: userlogin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập - XLight</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6f42c1;
            --secondary-color: #8a5cd0;
            --gradient-start: #6f42c1;
            --gradient-end: #8a5cd0;
        }
        
        body {
            background-image: url('../images/background.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-container {
            width: 100%;
            max-width: 400px;
            padding: 15px;
        }
        .card {
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(111, 66, 193, 0.3);
            backdrop-filter: blur(10px);
        }
        .card-header {
            background: linear-gradient(45deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            text-align: center;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            background-color: rgba(255, 255, 255, 0.9);
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
            border-color: var(--primary-color);
        }
        .btn {
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(45deg, var(--gradient-start), var(--gradient-end));
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(45deg, var(--gradient-end), var(--gradient-start));
            transform: translateY(-2px);
        }
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-outline-primary:hover {
            background: linear-gradient(45deg, var(--gradient-start), var(--gradient-end));
            border-color: transparent;
        }
        .input-group-text {
            border-radius: 10px 0 0 10px;
            background: linear-gradient(45deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            width: 45px;
            justify-content: center;
        }
        .alert {
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.9);
        }
        #registerForm {
            display: none;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Form Đăng nhập -->
        <div class="card" id="loginForm">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-user-circle me-2"></i>Đăng nhập</h4>
            </div>
            <div class="card-body p-4">
                <form method="post">
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="Tên đăng nhập" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="Mật khẩu" required>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="userlogin" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="toggleForm()">
                            <i class="fas fa-user-plus me-2"></i>Đăng ký tài khoản mới
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Form Đăng ký -->
        <div class="card" id="registerForm">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Đăng ký</h4>
            </div>
            <div class="card-body p-4">
                <form method="post">
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="reg_username" class="form-control" placeholder="Tên đăng nhập" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="Email" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" name="reg_password" class="form-control" placeholder="Mật khẩu" required>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="userregister" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Đăng ký
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="toggleForm()">
                            <i class="fas fa-arrow-left me-2"></i>Quay lại đăng nhập
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleForm() {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            
            if (loginForm.style.display === 'none') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
            }
        }
    </script>
</body>
</html>

