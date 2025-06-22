<?php
session_start();
ob_start();
require_once "includes/config.php";

// Kiểm tra đăng nhập
$user_logged_in = isset($_SESSION['user']) && isset($_SESSION['user']['id']);
$user_id = $user_logged_in ? (int)$_SESSION['user']['id'] : null;

if (!$user_logged_in || !$user_id) {
  $_SESSION['error_message'] = "Bạn cần đăng nhập để thanh toán.";
  header('Location: login.php');
  exit();
}

// Lấy giỏ hàng từ cơ sở dữ liệu
$cart_items = [];
$total = 0;
$stmt = $conn->prepare("SELECT p.id, p.name, p.price, p.main_img, c.quantity 
                        FROM cart c 
                        JOIN products p ON c.product_id = p.id 
                        WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($cart_items as $item) {
  $total += $item['price'] * $item['quantity'];
}

// Áp dụng giảm giá từ session nếu có
$original_total = $total;
$voucher_code = isset($_SESSION['voucher']) ? $_SESSION['voucher']['code'] : null;
$voucher_discount = 0;
if (isset($_SESSION['voucher']) && isset($_SESSION['voucher']['discount'])) {
  $voucher_discount = $_SESSION['voucher']['discount'];
  $total = $total * (1 - $voucher_discount);
}

if (empty($cart_items)) {
  $_SESSION['error_message'] = "Giỏ hàng của bạn đang trống.";
  header('Location: cart.php');
  exit();
}

// Xử lý thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
  $full_name = trim($_POST['full_name'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $province = trim($_POST['province'] ?? '');
  $district = trim($_POST['district'] ?? '');
  $ward = trim($_POST['ward'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $payment_method = $_POST['payment_method'] ?? 'cash_on_delivery';

  if (empty($full_name) || empty($phone) || empty($province) || empty($district) || empty($ward) || empty($address)) {
    $_SESSION['error_message'] = "Vui lòng điền đầy đủ thông tin.";
    header('Location: payment.php');
    exit();
  }

  // Kiểm tra mã giảm giá nếu có
  $voucher_code = isset($_SESSION['voucher']) ? $_SESSION['voucher']['code'] : null;
  if ($voucher_code) {
    $stmt = $conn->prepare("SELECT discount FROM vouchers WHERE code = ? AND is_active = TRUE");
    $stmt->bind_param("s", $voucher_code);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
      $voucher_code = null; // Mã không hợp lệ hoặc bị vô hiệu hóa
    }
  }

  // Lưu đơn hàng
  $stmt = $conn->prepare("INSERT INTO orders (user_id, full_name, phone, province, district, ward, address, payment_method, total, voucher_code, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
  $stmt->bind_param("isssssssds", $user_id, $full_name, $phone, $province, $district, $ward, $address, $payment_method, $total, $voucher_code);
  $stmt->execute();
  $order_id = $conn->insert_id;

  // Lưu chi tiết đơn hàng
  $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
  foreach ($cart_items as $item) {
    $effective_price = $item['price'] * (1 - $voucher_discount); // Giá sau giảm
    $stmt->bind_param("iidd", $order_id, $item['id'], $item['quantity'], $effective_price);
    $stmt->execute();
  }

  // Xóa giỏ hàng
  $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();

  // Xóa thông tin voucher khỏi session
  unset($_SESSION['voucher']);

  ob_end_clean();
  $_SESSION['success_message'] = "Đặt hàng thành công! Mã đơn hàng: #$order_id.";
  header('Location: index.php');
  exit();
}

// Hiển thị thông báo lỗi nếu có
$error_message = $_SESSION['error_message'] ?? '';
if ($error_message) {
  unset($_SESSION['error_message']);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" />
  <title>Thanh Toán - H-Computer</title>
  <link rel="stylesheet" href="css/payment.css" />
</head>

<body>
  <section id="header">
    <a href="index.php"><img src="image/logo.png" alt="logo" class="logo" /></a>
    <div class="slogan">
      <h3>Công Nghệ H-Computer Hướng Tới Khách Hàng</h3>
      <h3>Khách Hàng Hướng Tới Tương Lai</h3>
    </div>
    <div>
      <ul id="navbar">
        <li>
          <a href="index.php">
            <i class="fa-solid fa-house"></i> Trang chủ
          </a>
        </li>
        <li>
          <a href="shop.php"> <i class="fa-solid fa-store"></i> Cửa hàng </a>
        </li>
        <li>
          <a href="contact.php">
            <i class="fa-solid fa-phone"></i> Liên hệ
          </a>
        </li>
        <?php
        $username = $_SESSION['user'] ?? null;
        ?>
        <li>
          <a href="<?= isset($_SESSION['user']) ? 'profile.php' : 'login.php' ?>">
            <i class="fa-solid fa-user"></i>
            <?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['username']) : 'Đăng nhập' ?>
          </a>
        </li>
        <li id="la-bag">
          <a href="cart.php">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="cart-count" id="cart-count"></span> Giỏ Hàng
          </a>
        </li>
      </ul>
    </div>
  </section>

  <?php if ($error_message): ?>
    <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 10px; margin: 10px; border-radius: 5px; border: 1px solid #f5c6cb;">
      <strong>Lỗi:</strong> <?= htmlspecialchars($error_message) ?>
    </div>
  <?php endif; ?>

  <section id="prodetails" class="section-p1">
    <h2 style="color: #ff5d13">Trang Thanh Toán</h2>
    <div class="checkout-container">
      <div class="cart-section">
        <h3>Sản phẩm trong giỏ hàng</h3>
        <table>
          <thead>
            <tr>
              <td>Hình ảnh</td>
              <td>Sản phẩm</td>
              <td>Giá bán</td>
              <td>Số lượng</td>
              <td>Tổng thu</td>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cart_items as $item): ?>
              <?php
              $subtotal = $item['price'] * $item['quantity'];
              $discounted_subtotal = $subtotal * (1 - $voucher_discount);
              ?>
              <tr>
                <td><img src="<?= htmlspecialchars($item['main_img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 80px; border-radius: 8px;"></td>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
                <td><?= $item['quantity'] ?></td>
                <td><?= number_format($discounted_subtotal, 0, ',', '.') ?>đ</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <h3 style="text-align: right; margin-top: 20px;">Tổng cộng: <?= number_format($total, 0, ',', '.') ?>đ</h3>
      </div>

      <div class="payment-section">
        <h3>Thông tin thanh toán</h3>
        <form action="payment.php" method="POST">
          <input type="text" name="full_name" placeholder="Họ tên người nhận" required />
          <input type="tel" name="phone" placeholder="Số điện thoại" required />
          <input type="text" name="province" placeholder="Tỉnh/Thành phố" required />
          <input type="text" name="district" placeholder="Quận/Huyện" required />
          <input type="text" name="ward" placeholder="Phường/Xã" required />
          <input type="text" name="address" placeholder="Số nhà cụ thể" required />
          <div>
            <label><input type="radio" name="payment_method" value="cash_on_delivery" checked /> Trả tiền mặt khi nhận hàng</label>
            <label><input type="radio" name="payment_method" value="bank_transfer" /> Chuyển khoản ngân hàng</label>
          </div>
          <button type="submit" name="place_order">Xác nhận đặt hàng</button>
        </form>
      </div>
    </div>
  </section>

  <footer id="section-p1">
    <div class="col">
      <img class="logo" src="image/logo.png" />
      <h4>Thông tin liên hệ</h4>
      <p><strong>Địa chỉ: </strong> - 155 Đường A, Tây Hồ, Hoàn Kiếm, Hà Nội</p>
      <p><strong>Số điện thoại: </strong> (+84)9999869999 / (+84)8686998686</p>
      <p><strong>Giờ làm việc: </strong> 09:00 - 17:00, Mon - Sat</p>
      <div class="follow">
        <h4>Theo dõi chúng tôi qua nền tảng</h4>
        <div class="icon">
          <i class="fab fa-facebook-f"></i>
          <i class="fab fa-twitter"></i>
          <i class="fab fa-instagram"></i>
          <i class="fab fa-pinterest"></i>
          <i class="fab fa-youtube"></i>
        </div>
      </div>
    </div>
    <div class="col">
      <h4>Liên hệ</h4>
      <a href="#">Về chúng tôi</a>
      <a href="#">Thông tin vận chuyển</a>
      <a href="#">Chính sách bảo mật</a>
      <a href="#">Điều khoản & Điều kiện</a>
      <a href="#">Liên hệ chúng tôi</a>
    </div>
    <div class="col">
      <h4>Thông tin khác</h4>
      <a href="#">Tích điểm tặng quà VIP</a>
      <a href="#">Lịch sử mua hàng</a>
      <a href="#">Đăng ký bán hàng CTV chiết khấu cao</a>
      <a href="#">Tìm hiều về mua trả chậm</a>
      <a href="#">Hỗ trợ người dùng</a>
    </div>
    <div class="install">
      <h4>Tải ứng dụng</h4>
      <p>Từ App Store hoặc Google Play</p>
      <div class="row">
        <img src="image/pay/app.jpg" alt="" />
        <img src="image/pay/play.jpg" alt="" />
      </div>
      <p>Các phương thức thanh toán</p>
      <div class="pay">
        <img src="image/pay/pay1.png" alt="" />
        <img src="image/pay/pay2.png" alt="" />
        <img src="image/pay/pay3.png" alt="" />
        <img src="image/pay/pay4.png" alt="" />
      </div>
    </div>
  </footer>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const form = document.querySelector("form");
      const submitBtn = form.querySelector("button[type='submit']");

      if (form && submitBtn) {
        form.addEventListener("submit", function(event) {
          if (!confirm("Bạn có chắc chắn muốn đặt hàng?")) {
            event.preventDefault();
          }
        });
      }
    });
  </script>
</body>

</html>
<?php ob_end_flush(); ?>