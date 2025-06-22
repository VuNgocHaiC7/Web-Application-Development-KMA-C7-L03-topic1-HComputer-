<?php
session_start();
require_once 'includes/config.php';

// Biến thông báo
$success_message = '';
$error_message = '';

// Xử lý gửi form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $full_name = trim($_POST['full_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $message = trim($_POST['message'] ?? '');
  $user_id = $_SESSION['user']['id'] ?? null;

  if (empty($full_name) || empty($email) || empty($message)) {
    $error_message = "Vui lòng điền đầy đủ họ tên, email và nội dung.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error_message = "Email không hợp lệ.";
  } else {
    $stmt = $conn->prepare("INSERT INTO contact (user_id, full_name, email, phone, message) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $full_name, $email, $phone, $message);

    if ($stmt->execute()) {
      $success_message = "Thông tin liên hệ đã được gửi thành công.";
      $full_name = $email = $phone = $message = '';
    } else {
      $error_message = "Đã xảy ra lỗi khi gửi thông tin. Vui lòng thử lại.";
    }
    $stmt->close();
  }
}

// Kiểm tra giỏ hàng
$count = 0;
if (isset($_SESSION['user'])) {
  $user_id = $_SESSION['user']['id'];
  $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
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
  <link rel="stylesheet" href="css/contact.css" />
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
        <li><a href="index.php"><i class="fa-solid fa-house"></i> Trang chủ</a></li>
        <li><a href="shop.php"><i class="fa-solid fa-store"></i> Cửa hàng</a></li>
        <li><a class="active" href="contact.php"><i class="fa-solid fa-phone"></i> Liên hệ</a></li>
        <li>
          <a href="<?= isset($_SESSION['user']) ? 'profile.php' : 'login.php' ?>">
            <i class="fa-solid fa-user"></i>
            <?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['username']) : 'Đăng nhập' ?>
          </a>
        </li>
        <li id="la-bag">
          <a href="cart.php">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="cart-count" id="cart-count" <?= $count > 0 ? '' : 'style="display:none;"' ?>>
              <?= $count > 0 ? $count : '' ?>
            </span>
            Giỏ Hàng
          </a>
        </li>
      </ul>
    </div>
  </section>

  <!---->
  <section class="contact-section">
    <h2 style="color: #ff6600">Liên hệ với chúng tôi</h2>
    <div class="contact-container">
      <div class="contact-info">
        <h3>Thông tin liên hệ</h3>
        <p>
          <strong>Địa chỉ:</strong> 155 Đường A, Tây Hồ, Hoàn Kiếm, Hà Nội
        </p>
        <p><strong>Số điện thoại:</strong> (+84) 9999869999</p>
        <p><strong>Email:</strong> TeamNhaH@gmail.com</p>
        <p><strong>Website:</strong> Github</p>
        <p><strong>Mã số thuế:</strong> 07 03 18 20 22</p>
        <p><strong>Số tài khoản:</strong> 8686868686</p>
        <p><strong>Ngân hàng:</strong> MB Bank chi nhánh Hà Nội</p>
        <p>
          <strong>
            Quý khách có thể gửi liên hệ tới chúng tôi bằng cách hoàn tất biểu
            mẫu dưới đây. Chúng tôi sẽ trả lời thư của quý khách, xin vui lòng
            khai báo đầy đủ. Hân hạnh phục vụ và chân thành cảm ơn sự quan
            tâm, đóng góp ý kiến đến H-Computer.</strong>
        </p>
      </div>
      <div class="contact-map">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3132.597382504648!2d105.79663341810749!3d20.980323627792576!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135acc508f938fd%3A0x883e474806a2d1f2!2zSOG7jWMgdmnhu4duIEvhu7kgdGh14bqtdCBt4bqtdCBtw6M!5e0!3m2!1svi!2s!4v1749056387395!5m2!1svi!2s"
          width="100%"
          height="100%"
          frameborder="0"
          style="border: 0"
          allowfullscreen=""
          aria-hidden="false"
          tabindex="0"></iframe>
      </div>
    </div>

    <div class="contact-form-group">
      <div class="contact-form">
        <h3>Gửi thông tin liên hệ</h3>

        <?php if ($success_message): ?>
          <p style="color: green;"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>
        <?php if ($error_message): ?>
          <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form method="POST" action="contact.php">
          <input type="text" name="full_name" value="<?= htmlspecialchars($full_name ?? '') ?>" placeholder="Họ và tên*" required />
          <input type="text" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>" placeholder="Số điện thoại" />
          <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" placeholder="Email*" required />
          <textarea name="message" placeholder="Nội dung liên hệ*" rows="5" required><?= htmlspecialchars($message ?? '') ?></textarea>
          <button type="submit">Gửi</button>
        </form>
      </div>

      <div class="team-table">
        <h3>Thông tin thành viên nhóm</h3>
        <table>
          <thead>
            <tr>
              <th>STT</th>
              <th>Họ tên</th>
              <th>MSSV</th>
              <th>Lớp</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>1</td>
              <td>Vũ Ngọc Hải</td>
              <td>CT070318</td>
              <td>CT7C</td>
            </tr>
            <tr>
              <td>2</td>
              <td>Mai Việt Hoàng</td>
              <td>CT070320</td>
              <td>CT7C</td>
            </tr>
            <tr>
              <td>3</td>
              <td>Phạm Văn Hùng</td>
              <td>CT070322</td>
              <td>CT7C</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

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
  <!--Note Cart-->
  <?php

  $count = 0;
  if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $count = $result['total'] ?? 0;
  }
  ?>
  <script src="js/contact.js"></script>
</body>

</html>