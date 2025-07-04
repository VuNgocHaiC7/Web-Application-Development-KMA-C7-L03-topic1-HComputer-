<?php
session_start();
require_once "includes/config.php";

// Kiểm tra xem người dùng đã đăng nhập hay chưa
$user_logged_in = isset($_SESSION['user']) && isset($_SESSION['user']['id']);
$user_id = $user_logged_in ? $_SESSION['user']['id'] : null;

// Kiểm tra giỏ hàng
$count = 0;
if (isset($_SESSION['user'])) {
  $user_id = $_SESSION['user']['id'];
  $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

// Lấy ID sản phẩm từ URL
$product_id = (int)$_GET['id'];

// Truy vấn sản phẩm
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) {
  die("Sản phẩm không tồn tại");
}

// Truy vấn mô tả sản phẩm
$stmt = $conn->prepare("SELECT description FROM product_description WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$descriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy gallery (ảnh phụ của sản phẩm)
$stmt = $conn->prepare("SELECT * FROM product_gallery WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$gallery = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy sản phẩm gợi ý
$suggestions = [];
$stmt = $conn->prepare("SELECT * FROM products WHERE type = ? AND id != ? ORDER BY RAND() LIMIT 4");
$stmt->bind_param("si", $product['type'], $product_id);
$stmt->execute();
$suggestions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($suggestions)) {
  // Nếu không có sản phẩm cùng type, lấy ngẫu nhiên từ tất cả sản phẩm khác
  $stmt = $conn->prepare("SELECT * FROM products WHERE id != ? ORDER BY RAND() LIMIT 4");
  $stmt->bind_param("i", $product_id);
  $stmt->execute();
  $suggestions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Xử lý thêm sản phẩm vào giỏ hàng khi form được gửi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
  // Kiểm tra đăng nhập trước khi xử lý
  if (!$user_logged_in) {
    $_SESSION['error_message'] = "Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng.";
    header('Location: login.php');
    exit();
  }

  $quantity = isset($_POST['quantity']) && is_numeric($_POST['quantity']) && $_POST['quantity'] > 0 ? (int)$_POST['quantity'] : 1;

  // Người dùng đã đăng nhập: Lưu vào cơ sở dữ liệu
  $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
  $stmt->bind_param("ii", $user_id, $product_id);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    // Sản phẩm đã có trong giỏ hàng, cập nhật số lượng
    $row = $result->fetch_assoc();
    $new_quantity = $row['quantity'] + $quantity;
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("iii", $new_quantity, $user_id, $product_id);
    $stmt->execute();
  } else {
    // Thêm sản phẩm mới vào giỏ hàng
    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $user_id, $product_id, $quantity);
    $stmt->execute();
  }

  // Chuyển hướng đến trang giỏ hàng
  header('Location: cart.php');
  exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" />
  <?php
  // Hiển thị thông báo lỗi nếu có
  if (isset($_SESSION['error_message'])) {
    echo '<script>alert("' . htmlspecialchars($_SESSION['error_message']) . '");</script>';
    unset($_SESSION['error_message']);
  }
  ?>
  <title>H-Computer</title>
  <link rel="stylesheet" href="css/sproduct.css" />
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
          <a class="active" href="shop.php">
            <i class="fa-solid fa-store"></i> Cửa hàng
          </a>
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

  <!--Product Details-->
  <section id="prodetails" class="section-p1">
    <div class="pro-container">
      <!-- Ảnh chính và gallery -->
      <div class="single-pro-img">
        <img src="<?= htmlspecialchars($product['main_img']) ?>" id="MainImg" />
        <div class="small-img-group">
          <?php if (!empty($gallery)): ?>
            <?php foreach ($gallery as $img): ?>
              <div class="small-img-col">
                <img src="<?= htmlspecialchars($img['image_path']) ?>" class="small-img" />
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p>Không có ảnh phụ cho sản phẩm này.</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Thông tin cơ bản -->
      <div class="single-pro-details">
        <h1>Cửa hàng / <?= htmlspecialchars($product['type']) ?></h1>
        <h4><?= htmlspecialchars($product['name']) ?></h4>
        <h2><?= number_format($product['price']) ?>đ</h2>

        <div class="rating-card">
          <h4><i class="fas fa-star" style="color: #ff5d13;"></i> Đánh giá sản phẩm</h4>
          <div class="stars">
            <?= str_repeat('<i class="fas fa-star"></i>', (int)$product['rating']) ?>
            <?= str_repeat('<i class="far fa-star"></i>', 5 - (int)$product['rating']) ?>
          </div>
          <p>📊 <?= $product['rating_count'] ?> người đã đánh giá</p>
        </div>

        <div class="buy-section">
          <form id="add-to-cart-form" action="sproduct.php?id=<?= $product['id'] ?>" method="POST" data-is-logged-in="<?= $user_logged_in ? 'true' : 'false' ?>">
            <input type="number" name="quantity" id="qty" value="1" min="1">
            <button class="add-to-cart" type="submit" name="add_to_cart">🛒 Thêm vào giỏ hàng</button>
          </form>
        </div>
      </div>

      <!-- Chi tiết sản phẩm -->
      <div class="detailInformation">
        <h4>Thông tin chi tiết sản phẩm</h4>
        <div class="detail">
          <?php if (!empty($descriptions)): ?>
            <?php
            // Kết hợp tất cả mô tả thành một chuỗi duy nhất
            $fullDescription = implode(' ', array_column($descriptions, 'description'));
            // Tách thành các câu dựa trên dấu ;
            $sentences = array_map('trim', explode(';', $fullDescription));

            foreach ($sentences as $sentence) {
              $sentence = trim($sentence);
              if (!empty($sentence)) {
                if (strpos($sentence, ':') !== false) {
                  // Tách tiêu đề và giá trị dựa trên dấu :
                  [$label, $value] = array_map('trim', explode(':', $sentence, 2));
                  echo "<p><strong>{$label}:</strong> {$value}</p>";
                } else {
                  // Nếu không có dấu :, hiển thị nguyên câu
                  echo "<p>{$sentence}</p>";
                }
              }
            }
            ?>
          <?php else: ?>
            <p><em>Đang cập nhật...</em></p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!--Product other-->
  <section class="section-p1">
    <h2 class="section-title">Các Sản Phẩm Bạn Có Thể Thích</h2>
    <div class="suggest-container">
      <?php if (!empty($suggestions)): ?>
        <?php foreach ($suggestions as $item): ?>
          <div class="suggest-card">
            <a href="sproduct.php?id=<?= $item['id'] ?>">
              <img src="<?= htmlspecialchars($item['main_img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
              <div class="suggest-des">
                <h5 class="brand"><?= htmlspecialchars($item['brand']) ?></h5>
                <p class="product-name"><?= htmlspecialchars($item['name']) ?></p>
                <div class="star">
                  <?= str_repeat('<i class="fas fa-star"></i>', (int)$item['rating']) ?>
                  <?= str_repeat('<i class="far fa-star"></i>', 5 - (int)$item['rating']) ?>
                </div>
                <h4 class="price"><?= number_format($item['price']) ?>đ</h4>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>Không có sản phẩm gợi ý nào.</p>
      <?php endif; ?>
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

  <script src="js/sproduct.js"></script>
</body>

</html>