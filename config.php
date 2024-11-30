<?php
$host = 'localhost';
$dbname = 'trainingdb';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Thiết lập charset đơn giản hơn
    $conn->exec("SET NAMES utf8");
    $conn->exec("SET CHARACTER SET utf8");
    
} catch(PDOException $e) {
    die("Kết nối thất bại: " . $e->getMessage());
}
?>