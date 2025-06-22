<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voucher_code = $_POST['voucher_code'] ?? '';
    $discount = floatval($_POST['discount'] ?? 0);
    $discounted_total = floatval($_POST['discounted_total'] ?? 0);

    if ($voucher_code && $discount > 0 && $discounted_total > 0) {
        $_SESSION['voucher'] = [
            'code' => $voucher_code,
            'discount' => $discount,
            'discounted_total' => $discounted_total
        ];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
}
