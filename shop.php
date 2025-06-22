<?php
require_once "includes/config.php";
session_start();

// Kiểm tra giỏ hàng
$count = 0;
if (isset($_SESSION['user'])) {
  $user_id = $_SESSION['user']['id'];
  $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

// Phân trang
$perPage = 16;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;
if ($offset < 0) $offset = 0;

// Điều kiện lọc
$where = "WHERE 1=1";
$params = [];
$types = "";
$valid_types = ['Camera', 'Máy tính', 'Điện thoại'];
$valid_brands = ['IMOU', 'TP-LINK', 'TIANDY', 'EZVIZ', 'ASUS', 'ACER', 'MSI', 'MACBOOK', 'SAMSUNG', 'HONOR', 'IPHONE', 'OPPO', 'XIAOMI'];

// Lọc theo loại sản phẩm
if (!empty($_GET['type']) && $_GET['type'] !== 'all' && in_array($_GET['type'], $valid_types)) {
  $where .= " AND type = ?";
  $params[] = $_GET['type'];
  $types .= "s";
}

// Lọc theo thương hiệu
if (!empty($_GET['brand']) && $_GET['brand'] !== 'all' && in_array($_GET['brand'], $valid_brands)) {
  $where .= " AND brand = ?";
  $params[] = $_GET['brand'];
  $types .= "s";
}

// Lọc theo mức giá
if (!empty($_GET['price']) && $_GET['price'] !== 'all') {
  switch ($_GET['price']) {
    case 'low':
      $where .= " AND price < 5000000";
      break;
    case 'medium':
      $where .= " AND price BETWEEN 5000000 AND 15000000";
      break;
    case 'high':
      $where .= " AND price > 15000000";
      break;
  }
}

// Lọc theo đánh giá
if (!empty($_GET['rating']) && $_GET['rating'] !== 'all' && is_numeric($_GET['rating']) && $_GET['rating'] >= 1 && $_GET['rating'] <= 5) {
  $where .= " AND rating >= ?";
  $params[] = (int)$_GET['rating'];
  $types .= "i";
}

// Tìm kiếm
if (!empty($_GET['search'])) {
  $search = mysqli_real_escape_string($conn, $_GET['search']);
  $where .= " AND (name LIKE ? OR brand LIKE ? OR type LIKE ?)";
  $params[] = '%' . $search . '%';
  $params[] = '%' . $search . '%';
  $params[] = '%' . $search . '%';
  $types .= "sss";
}

// Đếm tổng sản phẩm
$count_sql = "SELECT COUNT(*) AS total FROM products $where";
$stmt = $conn->prepare($count_sql);
if ($types) {
  if (!$stmt->bind_param($types, ...$params)) {
    error_log("Bind param failed (count): " . $stmt->error);
    die("Lỗi truy vấn dữ liệu");
  }
}
if (!$stmt->execute()) {
  error_log("SQL Error (count): " . $stmt->error);
  die("Lỗi truy vấn dữ liệu");
}
$total = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($total / $perPage);

// Lấy danh sách sản phẩm
$data_sql = "SELECT * FROM products $where LIMIT ?, ?";
$stmt = $conn->prepare($data_sql);
$data_params = $params;
$data_types = $types . "ii";
$data_params[] = $offset;
$data_params[] = $perPage;

if (!$stmt->bind_param($data_types, ...$data_params) || !$stmt->execute()) {
  error_log("SQL Error (data): " . $stmt->error);
  die("Lỗi truy vấn dữ liệu");
}
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" />
  <title>H-Computer</title>
  <link rel="stylesheet" href="css/shop.css" />
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
        <li><a class="active" href="shop.php"><i class="fa-solid fa-store"></i> Cửa hàng</a></li>
        <li><a href="contact.php"><i class="fa-solid fa-phone"></i> Liên hệ</a></li>
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

  <section id="page-header">
    <h2>Danh mục các sản phẩm</h2>
  </section>

  <section id="filters" class="section-p1">
    <form id="filter-form" method="GET">
      <label for="type">Loại sản phẩm:</label>
      <select id="type" name="type" onchange="this.form.submit()">
        <option value="all">Tất cả</option>
        <option value="Camera" <?= ($_GET['type'] ?? '') == 'Camera' ? 'selected' : '' ?>>Camera</option>
        <option value="Máy tính" <?= ($_GET['type'] ?? '') == 'Máy tính' ? 'selected' : '' ?>>Máy tính</option>
        <option value="Điện thoại" <?= ($_GET['type'] ?? '') == 'Điện thoại' ? 'selected' : '' ?>>Điện thoại</option>
      </select>

      <label for="brand">Thương hiệu:</label>
      <select id="brand" name="brand" onchange="this.form.submit()">
        <option value="all">Tất cả</option>
        <?php
        $brands = ['IMOU', 'TP-LINK', 'TIANDY', 'EZVIZ', 'ASUS', 'ACER', 'MSI', 'MACBOOK', 'SAMSUNG', 'HONOR', 'IPHONE', 'OPPO', 'XIAOMI'];
        foreach ($brands as $b) {
          $sel = ($_GET['brand'] ?? '') == $b ? 'selected' : '';
          echo "<option value=\"$b\" $sel>$b</option>";
        }
        ?>
      </select>

      <label for="price">Mức giá:</label>
      <select id="price" name="price" onchange="this.form.submit()">
        <option value="all">Tất cả</option>
        <option value="low" <?= ($_GET['price'] ?? '') == 'low' ? 'selected' : '' ?>>Dưới 5 triệu</option>
        <option value="medium" <?= ($_GET['price'] ?? '') == 'medium' ? 'selected' : '' ?>>5 - 15 triệu</option>
        <option value="high" <?= ($_GET['price'] ?? '') == 'high' ? 'selected' : '' ?>>Trên 15 triệu</option>
      </select>

      <label for="rating">Đánh giá:</label>
      <select id="rating" name="rating" onchange="this.form.submit()">
        <option value="all">Tất cả</option>
        <?php for ($i = 5; $i >= 1; $i--): ?>
          <option value="<?= $i ?>" <?= ($_GET['rating'] ?? '') == "$i" ? 'selected' : '' ?>>
            <?= $i ?> sao trở lên
          </option>
        <?php endfor; ?>
      </select>

      <div id="search-bar">
        <input type="text" id="search-input" name="search" placeholder="Tìm kiếm sản phẩm..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" />
        <button id="search-btn" type="submit">Tìm</button>
        <a href="shop.php" id="clear-btn">Xóa bộ lọc</a>
      </div>
    </form>
  </section>

  <section id="product1" class="section-p1">
    <div id="pro-container" class="pro-container">
      <?php if ($result->num_rows === 0): ?>
        <p>Không tìm thấy sản phẩm nào. Vui lòng thử từ khóa khác.</p>
      <?php else: ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="pro"
            data-type="<?= htmlspecialchars($row['type']) ?>"
            data-brand="<?= htmlspecialchars($row['brand']) ?>"
            data-price="<?= htmlspecialchars($row['price']) ?>"
            data-rating="<?= htmlspecialchars($row['rating']) ?>">
            <a href="sproduct.php?id=<?= $row['id'] ?>">
              <img src="<?= htmlspecialchars($row['main_img']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" />
              <div class="des">
                <span><?= htmlspecialchars($row['brand']) ?></span>
                <h5><?= htmlspecialchars($row['name']) ?></h5>
                <div class="star">
                  <?= str_repeat('<i class="fas fa-star"></i>', (int)$row['rating']) ?>
                  <?= str_repeat('<i class="far fa-star"></i>', 5 - (int)$row['rating']) ?>
                </div>
                <h4><?= number_format($row['price']) ?>đ</h4>
              </div>
            </a>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </section>

  <div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>

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

  <script src="js/shop.js"></script>
</body>

</html>