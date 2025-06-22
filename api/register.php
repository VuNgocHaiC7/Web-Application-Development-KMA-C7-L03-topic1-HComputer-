<?php
require_once "../includes/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST["fullname"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($fullname) || empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Vui lòng nhập đầy đủ thông tin."]);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email đã được sử dụng."]);
        exit;
    }
    $stmt->close();

    $hashed_password = $password;
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $fullname, $email, $hashed_password);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Đăng ký thành công!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Lỗi khi đăng ký."]);
    }

    $stmt->close();
    $conn->close();
}
?>
