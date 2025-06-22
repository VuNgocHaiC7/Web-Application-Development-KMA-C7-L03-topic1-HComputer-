<?php
session_start();
require_once "includes/config.php";

if (!isset($_SESSION['user'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user']['id']; // Lấy ID từ session
$username = $_SESSION['user']['username']; // Lấy username từ session

// Lấy email của người dùng (chỉ cần 1 truy vấn duy nhất)
$user_query = $conn->prepare("SELECT email FROM users WHERE id = ?");
if ($user_query === false) {
  die("Lỗi prepare: " . $conn->error);
}
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_query->bind_result($email);
$user_query->fetch();
$user_query->close();

// Lấy danh sách đơn hàng của user với kiểm tra lỗi
$order_query = $conn->prepare("SELECT id, total, order_date, status FROM orders WHERE user_id = ? ORDER BY order_date DESC");
if ($order_query === false) {
  die("Lỗi prepare: " . $conn->error);
}
$order_query->bind_param("i", $user_id);
$order_query->execute();
$orders = $order_query->get_result();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Trang cá nhân</title>
  <link rel="stylesheet" href="css/profile.css"> <!-- dùng CSS riêng -->
</head>

<body>
  <div class="profile-wrapper">
    <div class="profile-card">
      <a class="logout-btn" href="logout.php">Đăng xuất</a>
      <a href="javascript:history.back()" class="back-btn">Trở lại</a>
      <h2>Xin chào, <span class="highlight"><?= htmlspecialchars($username) ?></span></h2>

      <!-- PHẦN 1: Thông tin cá nhân -->
      <section class="section personal-info">
        <h3>🔐 Thông tin cá nhân</h3>
        <div class="info-item"><strong>Tên đăng nhập:</strong> <?= htmlspecialchars($username) ?></div>
        <div class="info-item"><strong>Email:</strong> <?= htmlspecialchars($email ?? 'Chưa cập nhật') ?></div>
      </section>

      <!-- PHẦN 2: Lịch sử mua hàng -->
      <section class="section order-history">
        <h3>🧾 Lịch sử mua hàng</h3>

        <?php if ($orders && $orders->num_rows > 0): ?>
          <table class="order-table">
            <thead>
              <tr>
                <th>Mã đơn</th>
                <th>Ngày mua</th>
                <th>Sản phẩm</th>
                <th>Số lượng</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($order = $orders->fetch_assoc()): ?>
                <?php
                $item_stmt = $conn->prepare("
                        SELECT p.name AS product_name, oi.quantity
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        WHERE oi.order_id = ?
                    ");
                if ($item_stmt === false) {
                  die("Lỗi prepare: " . $conn->error);
                }
                $item_stmt->bind_param("i", $order['id']);
                $item_stmt->execute();
                $items = $item_stmt->get_result();

                $product_display = '';
                $total_quantity = 0;
                while ($item = $items->fetch_assoc()) {
                  $product_display .= htmlspecialchars($item['product_name']) . " (x" . $item['quantity'] . ")<br>";
                  $total_quantity += $item['quantity'];
                }
                $item_stmt->close();
                ?>
                <tr>
                  <td>#<?= $order['id'] ?></td>
                  <td><?= date("d/m/Y H:i", strtotime($order['order_date'])) ?></td>
                  <td><?= $product_display ?: 'Chưa có sản phẩm' ?></td>
                  <td><?= $total_quantity ?: 0 ?></td>
                  <td><?= number_format($order['total'], 0, ',', '.') ?>₫</td>
                  <td><?= htmlspecialchars($order['status']) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="no-order">Chưa có đơn hàng nào.</p>
        <?php endif; ?>
      </section>
    </div>
  </div>
</body>

</html>