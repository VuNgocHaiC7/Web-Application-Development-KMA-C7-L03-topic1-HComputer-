<?php
$host = "localhost";
$user = "root";
$pass = ""; // Đặt mật khẩu MySQL nếu có
$dbname = "h_computer"; // Tên database

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
// Đặt chế độ lỗi để bắt các lỗi SQL
$conn->set_charset("utf8mb4");
