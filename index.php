<?php
session_start();
require_once "includes/config.php";

// Tính số lượng sản phẩm trong giỏ hàng
$count = 0;
if (isset($_SESSION['user'])) {
  $user_id = $_SESSION['user']['id'];
  $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();
  $count = $result['total'] ?? 0;
} else {
  // Nếu chưa đăng nhập, tính dựa trên session (giả sử session['cart'] có cấu trúc như trong cart.php)
  if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
      $count += $item['quantity'];
    }
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
  <link
    href="https://fonts.googleapis.com/css2?family=Roboto&display=swap"
    rel="stylesheet" />
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
  <link rel="stylesheet" href="style.css" />
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
          <a class="active" href="index.php">
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
            <span class="cart-count" id="cart-count" <?= $count > 0 ? '' : 'style="display:none;"' ?>>
              <?= $count > 0 ? $count : '' ?>
            </span>
            Giỏ Hàng
          </a>
        </li>
      </ul>
    </div>
  </section>

  <!--Home page-->
  <section id="hero">
    <h4>Ưu Đãi Sập Sàn Cùng Mùa Hè</h4>
    <h2>Cùng với các món quà giá trị</h2>
    <h1>Tất cả sản phẩm của H-Computer</h1>
    <p>Tiết kiệm ví tiền của bạn bằng voucher của chúng tôi lên đến 90%</p>
    <button>Mua Ngay</button>
    <img src="image/m1.png" class="corner-img top-left" />
    <img src="image/m2.png" class="corner-img top-right" />
    <img src="image/m3.png" class="corner-img bottom-left" />
    <img src="image/m4.png" class="corner-img bottom-right" />
  </section>

  <!--Feature-->
  <section id="feature" class="section-p1">
    <h2>Ưu Đãi Dành Cho Khách Hàng</h2>
    <p>Chỉ có trên H-Computer</p>
    <div class="fe-box">
      <img src="image/features/f1.png" />
      <h6>Miễn phí vận chuyển</h6>
    </div>
    <div class="fe-box">
      <img src="image/features/f2.png" />
      <h6>Đặt hàng trực tuyến</h6>
    </div>
    <div class="fe-box">
      <img src="image/features/f3.png" />
      <h6>Tiết kiệm tiền</h6>
    </div>
    <div class="fe-box">
      <img src="image/features/f4.png" />
      <h6>Khuyến mãi</h6>
    </div>
    <div class="fe-box">
      <img src="image/features/f5.png" />
      <h6>Bán vui vẻ</h6>
    </div>
    <div class="fe-box">
      <img src="image/features/f6.png" />
      <h6>Hỗ trợ 24/7</h6>
    </div>
  </section>

  <!--Product-->
  <section id="product1" class="section-p1">
    <h2>Sản Phẩm Nổi Bật</h2>
    <p>Bộ Sản Phẩm Có Thương Hiệu Bán Chạy</p>
    <div class="pro-container">
      <div class="pro" data-id="1">
        <img src="image/product/f1.jpg" />
        <div class="des">
          <span>IMOU</span>
          <h5>Camera IP 360 Độ 3MP IMOU TA32CP-L</h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="far fa-star"></i>
          </div>
          <h4>470.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="2">
        <img src="image/product/f2.jpg" />
        <div class="des">
          <span>TP-LINK</span>
          <h5>Camera IP Ngoài Trời 360 Độ 2MP TP-Link Tapo C500</h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="far fa-star"></i>
            <i class="far fa-star"></i>
          </div>
          <h4>650.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="3">
        <img src="image/product/f3.jpg" />
        <div class="des">
          <span>ASUS</span>
          <h5>
            Laptop Asus Vivobook 15 OLED A1505ZA i5
            12500H/16GB/512GB/120Hz/Win11 (MA415W)
          </h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
          </div>
          <h4>16.190.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="4">
        <img src="image/product/f4.jpg" />
        <div class="des">
          <span>ACER</span>
          <h5>
            Laptop Acer Aspire Go AG15 31P 30M4 i3 N305/8GB/256GB/Win11
            (NX.KRPSV.004)
          </h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="far fa-star"></i>
          </div>
          <h4>10.090.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="5">
        <img src="image/product/f5.jpg" />
        <div class="des">
          <span>EZVIZ</span>
          <h5>Camera IP Ngoài Trời 2MP Ezviz H3C</h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
          </div>
          <h4>700.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="6">
        <img src="image/product/f6.jpg" />
        <div class="des">
          <span>SAMSUNG</span>
          <h5>Điện thoại Samsung Galaxy Z Fold6 5G 12GB/256GB</h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
          </div>
          <h4>37.990.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="7">
        <img src="image/product/f7.jpg" />
        <div class="des">
          <span>HONOR</span>
          <h5>Điện thoại HONOR X5b 4GB/64GB</h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="far fa-star"></i>
            <i class="far fa-star"></i>
          </div>
          <h4>2.140.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="8">
        <img src="image/product/f8.jpg" />
        <div class="des">
          <span>MACBOOK</span>
          <h5>Laptop MacBook Air 13 inch M2 16GB/256GB</h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
          </div>
          <h4>21.190.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
    </div>
  </section>

  <!--Banner Repair-->
  <section id="banner" class="section-m1">
    <h3>Dịch vụ sửa chữa</h3>
    <h2>Lên đến <span>Giảm 40%</span> - Tất cả các thiết bị điện tử</h2>
    <button class="normal">Tìm hiểu thêm</button>
  </section>

  <!--New Product-->
  <section id="product1" class="section-p1">
    <h2>Sản Phẩm Mới</h2>
    <p>Bộ Sản Phẩm Dành Cho Học Sinh Sinh Viên</p>
    <div class="pro-container">
      <div class="pro" data-id="9">
        <img src="image/new_product/n1.jpg" />
        <div class="des">
          <span>IPHONE</span>
          <h5>Điện thoại iPhone 16 Pro Max 256GB</h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="far fa-star"></i>
          </div>
          <h4>30.590.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="10">
        <img src="image/new_product/n2.jpg" />
        <div class="des">
          <span>SAMSUNG</span>
          <h5>Điện thoại Samsung Galaxy A36 5G 8GB/128GB</h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
          </div>
          <h4>8.090.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="11">
        <img src="image/new_product/n3.jpg" />
        <div class="des">
          <span>OPPO</span>
          <h5>Điện thoại OPPO Reno13 F 8GB/256GB</h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="far fa-star"></i>
            <i class="far fa-star"></i>
          </div>
          <h4>8.990.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="12">
        <img src="image/new_product/n4.jpg" />
        <div class="des">
          <span>MSI</span>
          <h5>
            Laptop MSI Gaming GF63 Thin 12VE i5 12450H/16GB/512GB/6GB
            RTX4050/144Hz/Win11 (460VN)
          </h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
          </div>
          <h4>19.690.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="13">
        <img src="image/new_product/n5.jpg" />
        <div class="des">
          <span>EZVIZ</span>
          <h5>Camera IP 360 Độ 8MP Ezviz C6C</h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="far fa-star"></i>
            <i class="far fa-star"></i>
          </div>
          <h4>1.290.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="14">
        <img src="image/new_product/n6.jpg" />
        <div class="des">
          <span>XIAOMI</span>
          <h5>Máy tính bảng Xiaomi Redmi Pad Pro WiFi 8GB/128GB</h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
          </div>
          <h4>6.990.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="15">
        <img src="image/new_product/n7.jpg" />
        <div class="des">
          <span>TIANDY</span>
          <h5>Camera IP Ngoài Trời 360 Độ 3MP TIANDY TC-H333N</h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="far fa-star"></i>
          </div>
          <h4>600.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
      <div class="pro" data-id="16">
        <img src="image/new_product/n8.jpg" />
        <div class="des">
          <span>ACER</span>
          <h5>
            Laptop Acer Gaming Nitro 5 Tiger AN515 58 52SP i5
            12500H/8GB/512GB/4GB RTX3050/144Hz/Win11 (NH.QFHSV.001)
          </h5>
          <div class="star">
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
            <i class="fas fa-star"></i>
          </div>
          <h4>18.690.000đ</h4>
        </div>
        <a href="#"><i class="fa-solid fa-cart-plus cart"></i></a>
      </div>
    </div>
  </section>

  <!-- Banner quảng cáo sử dụng Owl Carousel -->
  <section id="sm-banner" class="section-p1">
    <div class="owl-carousel owl-theme multi-banner-carousel">
      <div class="item">
        <img src="image/banner/b2.jpg" />
      </div>
      <div class="item">
        <img src="image/banner/b3.jpg" />
      </div>
      <div class="item">
        <img src="image/banner/b4.jpg" />
      </div>
      <div class="item">
        <img src="image/banner/b5.jpg" />
      </div>
      <div class="item">
        <img src="image/banner/b6.jpg" />
      </div>
      <div class="item">
        <img src="image/banner/b7.jpg" />
      </div>
      <div class="item">
        <img src="image/banner/bf7.jpg" />
      </div>
      <div class="item">
        <img src="image/banner/ba7.jpg" />
      </div>
      <div class="item">
        <img src="image/banner/bc7.jpg" />
      </div>
    </div>
  </section>

  <!--Other Banner-->
  <section id="ot-banner" class="section-p1">
    <div class="banner-box">
      <h4>Ưu đãi bất ngờ</h4>
      <h2>Mua 1 tặng voucher giảm 90%</h2>
      <span>Iphone 16 ProMax đang được bán tại H-Computer</span>
      <button class="white">Mua Ngay</button>
    </div>
    <div class="banner-box banner-box2">
      <h4>Laptop Sinh Viên</h4>
      <h2>Ưu đãi cho sinh viên tốt nghiệp THPT</h2>
      <span>Đừng bỏ lỡ</span>
      <button class="white">Tìm Hiểu Thêm</button>
    </div>
  </section>
  <!--Other 2 Banner-->
  <section id="ot-banner2">
    <div class="banner-box">
      <h2>GIẢM GIÁ SỐC</h2>
      <h3>Cùng với dàn chuột với thương hiệu độc quyền</h3>
    </div>
    <div class="banner-box banner-box2">
      <h2>ĐỪNG BỎ LỠ</h2>
      <h3>Hàng loạt những phụ kiện tô thêm vẻ đẹp cho thiết bị của bạn</h3>
    </div>
    <div class="banner-box banner-box3">
      <h2>AN TOÀN TIỆN LỢI</h2>
      <h3>Bằng những Camera hiện đại, tiên tiến</h3>
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

</body>

</html>