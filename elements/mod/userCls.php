<?php
class user {
    private $conn;
    
    public function __construct() {
        try {
            $host = 'localhost';
            $dbname = 'trainingdb';
            $username = 'root';
            $password = '';

            $this->conn = new PDO(
                "mysql:host=$host;dbname=$dbname",
                $username,
                $password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Debug kết nối
            error_log("Database connected successfully");
            
        } catch(PDOException $e) {
            error_log("Connection failed: " . $e->getMessage());
            throw new Exception("Lỗi kết nối database: " . $e->getMessage());
        }
    }

    public function UserCheckLogin($username, $password) {
        try {
            // In ra thông tin đăng nhập để debug
            error_log("Login attempt with username: " . $username);
            
            // Kiểm tra xem bảng users có tồn tại không
            $checkTable = $this->conn->query("SHOW TABLES LIKE 'users'");
            if($checkTable->rowCount() == 0) {
                error_log("Table 'users' does not exist!");
                // Tạo bảng nếu chưa có
                $this->createUsersTable();
            }
            
            // Query users với điều kiện chính xác
            $query = "SELECT * FROM users WHERE username = ? AND password = ? LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$username, $password]);
            
            // Debug thông tin query
            error_log("Query executed: " . $query);
            error_log("Parameters: username=" . $username . ", password=" . $password);
            error_log("Number of rows found: " . $stmt->rowCount());
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug kết quả
            if($result) {
                error_log("Login successful. User data: " . print_r($result, true));
            } else {
                error_log("Login failed. No matching user found.");
            }
            
            return $result;
            
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    private function createUsersTable() {
        try {
            // Tạo bảng users nếu chưa tồn tại
            $sql = "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(50) NOT NULL,
                is_admin TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            $this->conn->exec($sql);
            
            // Thêm tài khoản mặc định
            $sql = "INSERT INTO users (username, password, is_admin) VALUES 
                    ('admin', 'admin123', 1),
                    ('user1', 'user123', 0)";
            
            $this->conn->exec($sql);
            
            error_log("Users table created and default accounts added");
            
        } catch(PDOException $e) {
            error_log("Create users table error: " . $e->getMessage());
        }
    }

    // Thêm hàm đăng ký tài khoản mới
    public function UserRegister($username, $password, $is_admin = 0) {
        try {
            // Kiểm tra username đã tồn tại chưa
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại'];
            }
            
            // Thêm user mới
            $stmt = $this->conn->prepare("
                INSERT INTO users (username, password, is_admin) 
                VALUES (?, ?, ?)
            ");
            
            $result = $stmt->execute([$username, $password, $is_admin]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Đăng ký thành công'];
            } else {
                return ['success' => false, 'message' => 'Đăng ký thất bại'];
            }
            
        } catch(PDOException $e) {
            error_log("Register error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống'];
        }
    }
}
?> 