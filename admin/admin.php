<?php
session_start();
require_once "../includes/config.php";

// Kiểm tra quyền admin
if (!isset($_SESSION['admin'])) {
  $_SESSION['error_message'] = "Bạn cần đăng nhập với tài khoản admin để truy cập.";
  header('Location: ../login.php');
  exit();
}

// Tính số lượng bán ra (tổng quantity từ order_items)
$stmt = $conn->prepare("SELECT SUM(quantity) AS total_sold FROM order_items");
$stmt->execute();
$total_sold = $stmt->get_result()->fetch_assoc()['total_sold'] ?? 0;

// Tính doanh thu (tổng total từ orders với status != 'Cancelled')
$stmt = $conn->prepare("SELECT SUM(total) AS total_revenue FROM orders WHERE status != 'Cancelled'");
$stmt->execute();
$total_revenue = $stmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;

// Dữ liệu cho biểu đồ số lượng bán ra (ví dụ: theo tháng)
$sales_data = [];
$labels = [];
for ($i = 5; $i >= 0; $i--) {
  $month = date('Y-m', strtotime("-$i months"));
  $labels[] = date('M Y', strtotime("-$i months"));
  $stmt = $conn->prepare("SELECT SUM(quantity) AS total FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE DATE_FORMAT(o.order_date, '%Y-%m') = ? AND o.status != 'Cancelled'");
  $stmt->bind_param("s", $month);
  $stmt->execute();
  $sales_data[] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

// Dữ liệu cho biểu đồ doanh thu (ví dụ: theo tháng)
$revenue_data = [];
for ($i = 5; $i >= 0; $i--) {
  $month = date('Y-m', strtotime("-$i months"));
  $stmt = $conn->prepare("SELECT SUM(total) AS total FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = ? AND status != 'Cancelled'");
  $stmt->bind_param("s", $month);
  $stmt->execute();
  $revenue_data[] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>H-Computer: Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="..admin/css/style.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
          <a class="nav-link active bg-info rounded" href="admin.php"><i class="fas fa-home me-2"></i>Trang Chủ</a>
        </li>
        <li class="nav-item mb-2">
          <a class="nav-link" href="products_ad.php"><i class="fas fa-box me-2"></i>Sản Phẩm</a>
        </li>
        <li class="nav-item mb-2">
          <a class="nav-link" href="order_ad.php"><i class="fas fa-clipboard-list me-2"></i>Đơn Hàng</a>
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
    <main class="p-4 flex-grow-1">
      <div class="row">
        <div class="col-md-6 mb-4">
          <h4 class="mb-3">Số lượng bán ra: <?= (int)$total_sold ?> sản phẩm</h4>
          <canvas id="salesChart" height="200"></canvas>
        </div>
        <div class="col-md-6 mb-4">
          <h4 class="mb-3">Doanh thu: <?= number_format($total_revenue) ?>đ</h4>
          <canvas id="revenueChart" height="200"></canvas>
        </div>
      </div>
    </main>
  </div>

  <script>
    // Biểu đồ số lượng bán ra
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    new Chart(salesCtx, {
      type: 'bar',
      data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
          label: 'Số lượng bán ra',
          data: <?php echo json_encode($sales_data); ?>,
          backgroundColor: '#ff780a',
          borderColor: '#ff780a',
          borderWidth: 1
        }]
      },
      options: {
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    // Biểu đồ doanh thu
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
      type: 'line',
      data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
          label: 'Doanh thu (VNĐ)',
          data: <?php echo json_encode($revenue_data); ?>,
          backgroundColor: 'rgba(13, 110, 253, 0.2)',
          borderColor: '#0d6efd',
          borderWidth: 2,
          fill: true
        }]
      },
      options: {
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  </script>
  <script src="../js/script.js"></script>
</body>

</html>