<?php
session_start();
require_once "includes/config.php";

// Kiểm tra xem người dùng đã đăng nhập hay chưa
$user_logged_in = isset($_SESSION['user']) && isset($_SESSION['user']['id']);
$user_id = $user_logged_in ? $_SESSION['user']['id'] : null;

// Xử lý cập nhật số lượng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
  if ($user_logged_in) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
      $quantity = (int)$quantity;
      $product_id = (int)$product_id;
      if ($quantity <= 0) {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
      } else {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("iii", $quantity, $user_id, $product_id);
        $stmt->execute();
      }
    }
  } else {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
      $quantity = (int)$quantity;
      $product_id = (int)$product_id;
      foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $product_id) {
          if ($quantity <= 0) {
            unset($_SESSION['cart'][$key]);
          } else {
            $_SESSION['cart'][$key]['quantity'] = $quantity;
          }
          break;
        }
      }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
  }
  header('Location: cart.php');
  exit();
}

// Xử lý xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
  $product_id = (int)$_POST['remove_id'];
  if ($user_logged_in) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
  } else {
    foreach ($_SESSION['cart'] as $key => $item) {
      if ($item['id'] == $product_id) {
        unset($_SESSION['cart'][$key]);
        break;
      }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
  }

  // Làm mới dữ liệu giỏ hàng sau khi xóa
  $cart_items = [];
  $cart_count = 0;
  $total = 0;

  if ($user_logged_in) {
    $stmt = $conn->prepare("SELECT p.id, p.name, p.price, p.main_img, c.quantity
                            FROM cart c
                            JOIN products p ON c.product_id = p.id
                            WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $cart_items[] = $row;
      $cart_count += $row['quantity'];
      $total += $row['price'] * $row['quantity'];
    }
  } else {
    $cart_items = $_SESSION['cart'] ?? [];
    foreach ($cart_items as $item) {
      $cart_count += $item['quantity'];
      $total += $item['price'] * $item['quantity'];
    }
  }

  // Cập nhật lại tổng tiền sau giảm giá
  $display_total = $total;
  if (isset($_SESSION['voucher']) && isset($_SESSION['voucher']['discount']) && $total > 0) {
    $display_total = $total * (1 - $_SESSION['voucher']['discount']);
    $_SESSION['voucher']['discounted_total'] = $display_total;
  } else {
    // Nếu giỏ hàng trống, reset voucher
    if ($cart_count == 0) {
      unset($_SESSION['voucher']);
    }
  }

  header('Location: cart.php');
  exit();
}

// Xử lý áp dụng mã giảm giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_voucher'])) {
  $voucher_code = trim($_POST['voucher_code'] ?? '');
  $discount = 0;
  $total = 0;

  if ($user_logged_in) {
    $stmt = $conn->prepare("SELECT p.price, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
      $total += $row['price'] * $row['quantity'];
    }
  } else {
    foreach ($_SESSION['cart'] ?? [] as $item) {
      $total += $item['price'] * $item['quantity'];
    }
  }

  // Kiểm tra mã giảm giá hợp lệ (chỉ dựa vào is_active)
  $stmt = $conn->prepare("SELECT discount FROM vouchers WHERE code = ? AND is_active = TRUE");
  $stmt->bind_param("s", $voucher_code);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $discount = $row['discount'];
  } else {
    $_SESSION['error_message'] = "Mã giảm giá không hợp lệ hoặc đã bị vô hiệu hóa.";
    header('Location: cart.php');
    exit();
  }

  if ($total > 0) {
    $discounted_total = $total * (1 - $discount);
    $_SESSION['voucher'] = [
      'code' => $voucher_code,
      'discount' => $discount,
      'discounted_total' => $discounted_total
    ];
  } else {
    $_SESSION['error_message'] = "Giỏ hàng trống, không thể áp dụng voucher.";
    header('Location: cart.php');
    exit();
  }
  header('Location: cart.php');
  exit();
}

// Lấy dữ liệu giỏ hàng
$cart_items = [];
$cart_count = 0;
$total = 0;

if ($user_logged_in) {
  $stmt = $conn->prepare("SELECT p.id, p.name, p.price, p.main_img, c.quantity
                          FROM cart c
                          JOIN products p ON c.product_id = p.id
                          WHERE c.user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
    $cart_count += $row['quantity'];
    $total += $row['price'] * $row['quantity'];
  }
} else {
  $cart_items = $_SESSION['cart'] ?? [];
  foreach ($cart_items as $item) {
    $cart_count += $item['quantity'];
    $total += $item['price'] * $item['quantity'];
  }
}

// Áp dụng giảm giá nếu có trong session
$display_total = $total;
if (isset($_SESSION['voucher']) && isset($_SESSION['voucher']['discounted_total']) && $total > 0) {
  $display_total = $_SESSION['voucher']['discounted_total'];
} elseif ($cart_count == 0) {
  unset($_SESSION['voucher']); // Reset voucher nếu giỏ hàng trống
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" />
  <title>H-Computer</title>
  <link rel="stylesheet" href="css/cart.css" />
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
        <li><a href="contact.php"><i class="fa-solid fa-phone"></i> Liên hệ</a></li>
        <li><a href="<?= isset($_SESSION['user']) ? 'profile.php' : 'login.php' ?>"><i class="fa-solid fa-user"></i> <?= isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['username']) : 'Đăng nhập' ?></a></li>
        <li id="la-bag">
          <a class="active" href="cart.php">
            <i class="fa-solid fa-cart-shopping"></i>
            <span>Giỏ Hàng</span>
            <span class="cart-count" id="cart-count" <?= $cart_count > 0 ? '' : 'style="display:none;"' ?>>
              <?= $cart_count > 0 ? $cart_count : '' ?>
            </span>
          </a>
        </li>
      </ul>
    </div>
  </section>

  <!-- Giỏ Hàng -->
  <section id="background-cart">
    <h4>Giỏ Hàng Của Bạn</h4>
  </section>

  <section id="cart" class="section-p1">
    <table>
      <thead>
        <tr>
          <td>Loại bỏ</td>
          <td>Hình ảnh</td>
          <td>Sản phẩm</td>
          <td>Giá bán</td>
          <td>Số lượng</td>
          <td>Tổng thu</td>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cart_items as $item): ?>
          <?php $subtotal = $item['price'] * $item['quantity']; ?>
          <tr>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="remove_id" value="<?= $item['id'] ?>">
                <button type="submit" class="normal">Xóa</button>
              </form>
            </td>
            <td><img src="<?= htmlspecialchars($item['main_img']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" /></td>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= number_format($item['price'], 0, ',', '.') ?>đ</td>
            <td><input type="number" name="quantity[<?= $item['id'] ?>]" value="<?= $item['quantity'] ?>" min="0"></td>
            <td><?= number_format($subtotal, 0, ',', '.') ?>đ</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>

  <section id="cart-add" class="section-p1">
    <div id="coupon">
      <h3>Áp dụng voucher</h3>
      <form method="POST" id="voucher-form">
        <div>
          <input type="text" placeholder="Nhập mã giảm giá" style="font-size: 16px" id="voucher-code" name="voucher_code" />
          <button type="submit" class="normal" name="apply_voucher">Áp dụng</button>
        </div>
        <div class="voucher-list">
          <p>Các mã có thể áp dụng:</p>
          <ul>
            <li><code>GIAM10</code> – Giảm 10%</li>
            <li><code>GIAM20</code> – Giảm 20%</li>
          </ul>
        </div>
      </form>
    </div>

    <div id="subtotal">
      <h3>Tổng Giỏ Hàng</h3>
      <table id="subtotal-table">
        <tr>
          <td>Tổng thu</td>
          <td><?= number_format($display_total, 0, ',', '.') ?>đ</td>
        </tr>
        <tr>
          <td>Phí vận chuyển</td>
          <td>Miễn phí</td>
        </tr>
        <tr>
          <td>Tổng</td>
          <td><?= number_format($display_total, 0, ',', '.') ?>đ</td>
        </tr>
      </table>
      <button class="normal" id="checkout-btn" onclick="handleCheckout()">Tiến hành thanh toán</button>
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

  <script>
    function handleCheckout() {
      const isLoggedIn = <?= json_encode($user_logged_in) ?>;
      const cartCount = <?= $cart_count ?>;

      if (!isLoggedIn) {
        alert('Bạn cần đăng nhập');
        window.location.href = 'login.php';
        return;
      }

      if (cartCount === 0) {
        alert('Giỏ hàng của bạn đang trống');
        return;
      }

      window.location.href = 'payment.php';
    }
  </script>
</body>

</html>