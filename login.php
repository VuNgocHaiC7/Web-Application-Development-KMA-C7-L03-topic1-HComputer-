<?php
session_start();
require_once "includes/config.php";  // Kết nối cơ sở dữ liệu

// Kiểm tra nếu người dùng đã gửi thông tin đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Lấy thông tin người dùng từ form đăng nhập
  $username = $_POST['username'];
  $password = $_POST['password'];

  // Truy vấn cơ sở dữ liệu để kiểm tra thông tin người dùng
  $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $result = $stmt->get_result();
  $user = $result->fetch_assoc();

  // Kiểm tra nếu người dùng tồn tại và mật khẩu đúng
  if ($user && password_verify($password, $user['password'])) {
    // Nếu đăng nhập thành công, lưu thông tin vào session
    $_SESSION['user'] = [
      'id' => $user['id'],  // Lưu ID người dùng
      'username' => $user['username']  // Lưu tên người dùng
    ];
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" />
  <!-- Owl Carousel CSS -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css" />
  <!-- jQuery & Owl Carousel JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

  <title>H-Computer</title>
  <link rel="stylesheet" href="css/login.css" />
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
        <li>
          <?php if (isset($_SESSION['user'])): ?>
            <a href="profile.php"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($_SESSION['user']['username']) ?></a>
          <?php else: ?>
            <a class="active" href="login.php"><i class="fa-solid fa-user"></i> Đăng nhập</a>
          <?php endif; ?>
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

  <!--Đăng nhập-->
  <div class="login-section">
    <div class="container" id="login-container">
      <div class="tabs" id="tab-buttons">
        <button class="tab active" id="login-tab" onclick="showForm('login')">
          Đăng nhập
        </button>
        <button class="tab" id="register-tab" onclick="showForm('register')">
          Đăng kí
        </button>
      </div>

      <div class="form-container" id="form-container">
        <!-- Đăng nhập -->
        <form
          id="login-form"
          class="form active"
          method="POST"
          action="api/login.php">
          <h2>Nhập tài khoản</h2>
          <input
            id="login-username"
            name="username"
            type="text"
            placeholder="Tên đăng nhập*"
            required />
          <input
            id="login-password"
            name="password"
            type="password"
            placeholder="Mật khẩu*"
            required />
          <div class="forgot">
            <a id="forgot-password" href="#">Quên mật khẩu?</a>
          </div>
          <button type="submit" id="login-submit">ĐĂNG NHẬP</button>
        </form>

        <!-- Đăng ký -->
        <form id="register-form" class="form">
          <h2>Tạo tài khoản mới</h2>
          <input
            id="register-fullname"
            type="text"
            placeholder="Tên đăng nhập*"
            required />
          <input
            id="register-email"
            type="text"
            placeholder="Email hoặc SĐT*"
            required />
          <input
            id="register-password"
            type="password"
            placeholder="Mật khẩu*"
            required />
          <input
            id="confirm-password"
            type="password"
            placeholder="Xác nhận mật khẩu*"
            required />
          <button type="submit" id="register-submit">ĐĂNG KÝ</button>
        </form>
      </div>
    </div>
  </div>

  <!--Footer-->
  <footer id="section-p1">
    <div class="col">
      <img class="logo" src="image/logo.png" />
      <h4>Thông tin liên hệ</h4>
      <p>
        <strong>Địa chỉ: </strong> - 155 Đường A, Tây Hồ, Hoàn Kiếm, Hà Nội
      </p>
      <p>
        <strong>Số điện thoại: </strong> (+84)9999869999 / (+84)8686998686
      </p>
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
  <script src="js/login.js"></script>
</body>
<script>
  document
    .getElementById("register-form")
    .addEventListener("submit", async function(e) {
      e.preventDefault();
      const fullname = document.getElementById("register-fullname").value;
      const email = document.getElementById("register-email").value;
      const password = document.getElementById("register-password").value;

      const response = await fetch("api/register.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `fullname=${encodeURIComponent(
            fullname
          )}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(
            password
          )}`,
      });

      const result = await response.json();
      alert(result.message);
    });
</script>

</html>