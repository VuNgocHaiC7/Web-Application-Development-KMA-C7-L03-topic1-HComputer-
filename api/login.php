<?php
session_start();
require_once "../includes/config.php";

$username = trim($_POST["username"] ?? '');
$password = trim($_POST["password"] ?? '');

if ($username === '' || $password === '') {
    echo json_encode(["status" => "error", "message" => "Thiếu thông tin."]);
    exit;
}

// Ưu tiên kiểm tra trong bảng admins trước
$stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $_SESSION["admin"] = $username;
    echo json_encode(["status" => "admin", "message" => "Đăng nhập admin thành công!"]);
    exit;
}
$stmt->close();

// Nếu không phải admin, kiểm tra bảng users
$stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 1) {
    $stmt->bind_result($user_id, $db_password);
    $stmt->fetch();

    if ($password === $db_password) {
        // Lưu thông tin người dùng vào session dưới dạng mảng
        $_SESSION["user"] = [
            'id' => $user_id,         // Lưu ID người dùng
            'username' => $username,  // Lưu tên người dùng
        ];
        echo json_encode(["status" => "user", "message" => "Đăng nhập người dùng thành công!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Mật khẩu không đúng."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Không tìm thấy tài khoản."]);
}

$stmt->close();
$conn->close();
