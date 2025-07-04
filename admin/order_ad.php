<?php
session_start();
require_once "../includes/config.php";

// Kiểm tra quyền admin
if (!isset($_SESSION['admin'])) {
  $_SESSION['error_message'] = "Bạn cần đăng nhập với tài khoản admin để truy cập.";
  header('Location: ../login.php');
  exit();
}

// Lấy danh sách đơn hàng với kiểm tra lỗi
$stmt = $conn->prepare("SELECT * FROM orders ORDER BY order_date DESC"); // Sử dụng order_date thay vì created_at
if ($stmt === false) {
  die("Lỗi prepare: " . $conn->error); // Hiển thị lỗi cụ thể từ MySQL
}

$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>Quản lý đơn hàng - H-Computer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
</head>

<body>
  <div class="d-flex">
    <!-- Sidebar -->
    <aside class="bg-sidebar p-3 vh-100" style="width: 250px">
      <h4 class="fw-bold mb-4" style="color: #ff780a">
        H-Computer: Admin
        <img class="logo" src="../image/logo.png" alt="Logo" width="50" height="50" />
      </h4>
      <ul class="nav flex-column">
        <li class="nav-item mb-2">
          <a class="nav-link" href="admin.php"><i class="fas fa-home me-2"></i>Trang Chủ</a>
        </li>
        <li class="nav-item mb-2">
          <a class="nav-link" href="products_ad.php"><i class="fas fa-box me-2"></i>Sản Phẩm</a>
        </li>
        <li class="nav-item mb-2">
          <a class="nav-link active text-white bg-info rounded" href="order_ad.php"><i class="fas fa-clipboard-list me-2"></i>Đơn Hàng</a>
        </li>
        <li class="nav-item mb-2">
          <a class="nav-link" href="customers_ad.php"><i class="fas fa-users me-2"></i>Khách Hàng</a>
        </li>
        <li class="nav-item mt-4">
          <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất (về Trang chủ)</a>
        </li>
      </ul>
    </aside>

    <!-- Main content -->
    <main class="flex-grow-1 p-4">
      <h4 class="mb-4">Danh sách đơn hàng</h4>
      <div class="table-responsive">
        <table class="table table-dark table-bordered text-center align-middle">
          <thead>
            <tr>
              <th>STT</th>
              <th>Mã đơn hàng</th>
              <th>Họ tên</th>
              <th>Số điện thoại</th>
              <th>Tổng tiền</th>
              <th>Trạng thái</th>
              <th>Thời gian</th>
              <th>Hành động</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $index => $order): ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td><?= $order['id'] ?></td>
                <td><?= htmlspecialchars($order['full_name']) ?></td>
                <td><?= htmlspecialchars($order['phone']) ?></td>
                <td><?= number_format($order['total'], 2) ?>đ</td>
                <td><?= htmlspecialchars($order['status']) ?></td>
                <td><?= htmlspecialchars($order['order_date']) ?></td>
                <td>
                  <button class="btn btn-primary btn-sm" onclick="viewOrderDetails(<?= $order['id'] ?>)">Xem chi tiết</button>
                  <button class="btn btn-success btn-sm" onclick="updateOrderStatus(<?= $order['id'] ?>, 'Completed')">Hoàn thành</button>
                  <button class="btn btn-danger btn-sm" onclick="deleteOrder(<?= $order['id'] ?>)">Xóa</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function viewOrderDetails(orderId) {
      alert('Xem chi tiết đơn hàng ID: ' + orderId);
    }

    function updateOrderStatus(orderId, status) {
      if (confirm('Bạn có chắc muốn cập nhật trạng thái đơn hàng thành ' + status + '?')) {
        fetch('update_order_status.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'order_id=' + orderId + '&status=' + status
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) location.reload();
            else alert('Cập nhật thất bại!');
          });
      }
    }

    function deleteOrder(orderId) {
      if (confirm('Bạn có chắc muốn xóa đơn hàng này?')) {
        fetch('delete_order.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'order_id=' + orderId
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) location.reload();
            else alert('Xóa thất bại!');
          });
      }
    }
  </script>
</body>

</html>