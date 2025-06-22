<?php
session_start();
require_once "../includes/config.php";

// Kiểm tra quyền admin
if (!isset($_SESSION['admin'])) {
  $_SESSION['error_message'] = "Bạn cần đăng nhập với tài khoản admin để truy cập.";
  header('Location: ../login.php');
  exit();
}

// Xử lý tìm kiếm
$search_term = $_GET['search'] ?? '';
$search_type = $_GET['search_type'] ?? 'name';
$where = "WHERE 1=1";
$params = [];
$types = "";

if ($search_term) {
  if ($search_type === 'name') {
    $where .= " AND username LIKE ?";
  } else {
    $where .= " AND email LIKE ?";
  }
  $params[] = '%' . $search_term . '%';
  $types .= "s";
}

// Lấy danh sách khách hàng
$sql = "SELECT * FROM users $where";
$stmt = $conn->prepare($sql);
if ($types) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$customers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Xử lý thêm khách hàng
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
  $username = $_POST['addName'];
  $email = $_POST['addEmail'];
  $account = $_POST['addUsername'];
  $password = password_hash($_POST['addPassword'], PASSWORD_DEFAULT);

  $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $username, $email, $password);
  if ($stmt->execute()) {
    header('Location: customers_ad.php');
    exit();
  } else {
    $error = "Thêm khách hàng thất bại.";
  }
}

// Xử lý chỉnh sửa khách hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_customer'])) {
  $id = $_POST['editCustomerId'];
  $username = $_POST['editName'];
  $email = $_POST['editEmail'];
  $account = $_POST['editUsername'];
  $password = !empty($_POST['editPassword']) ? password_hash($_POST['editPassword'], PASSWORD_DEFAULT) : null;

  $sql = "UPDATE users SET username = ?, email = ?" . ($password ? ", password = ?" : "") . " WHERE id = ?";
  $stmt = $conn->prepare($sql);
  if ($password) {
    $stmt->bind_param("sssi", $username, $email, $password, $id);
  } else {
    $stmt->bind_param("ssi", $username, $email, $id);
  }
  if ($stmt->execute()) {
    header('Location: customers_ad.php');
    exit();
  } else {
    $error = "Chỉnh sửa khách hàng thất bại.";
  }
}

// Xử lý xóa khách hàng
if (isset($_GET['delete_id'])) {
  $id = $_GET['delete_id'];
  $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
  $stmt->bind_param("i", $id);
  if ($stmt->execute()) {
    header('Location: customers_ad.php');
    exit();
  } else {
    $error = "Xóa khách hàng thất bại.";
  }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>Quản lý khách hàng</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/style.css" />
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
          <a class="nav-link" href="order_ad.php"><i class="fas fa-clipboard-list me-2"></i>Đơn Hàng</a>
        </li>
        <li class="nav-item mb-2">
          <a class="nav-link active bg-info rounded" href="customers_ad.php"><i class="fas fa-users me-2"></i>Khách Hàng</a>
        </li>
        <li class="nav-item mt-4">
          <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng xuất (về Trang chủ)</a>
        </li>
      </ul>
    </aside>

    <!-- Main content -->
    <main class="flex-grow-1 p-4">
      <h4 class="mb-4">Danh sách khách hàng</h4>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <!-- Bộ lọc tìm kiếm -->
      <form method="GET" class="d-flex gap-2 align-items-center mb-3 flex-wrap">
        <select class="form-select w-auto" name="search_type">
          <option value="name" <?php echo $search_type === 'name' ? 'selected' : ''; ?>>Tìm theo tên đăng nhập</option>
          <option value="email" <?php echo $search_type === 'email' ? 'selected' : ''; ?>>Tìm theo email</option>
        </select>
        <input type="text" class="form-control w-25" name="search" placeholder="Tìm kiếm..." value="<?php echo htmlspecialchars($search_term); ?>" />
        <button type="submit" class="btn btn-dark"><i class="fas fa-search"></i> Tìm</button>
        <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
          <i class="fas fa-plus"></i> Thêm người dùng
        </button>
      </form>

      <!-- Bảng khách hàng -->
      <div class="table-responsive">
        <table class="table table-dark table-bordered text-center align-middle">
          <thead>
            <tr>
              <th>Stt</th>
              <th>Tên đăng nhập</th>
              <th>Email hoặc SĐT</th>
              <th>Tài khoản</th>
              <th>Mật khẩu</th>
              <th>Hành động</th>
            </tr>
          </thead>
          <tbody id="customer-table-body">
            <?php foreach ($customers as $index => $customer): ?>
              <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo htmlspecialchars($customer['username']); ?></td>
                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                <td><?php echo htmlspecialchars($customer['username']); ?></td>
                <td><?php echo htmlspecialchars($customer['password']); ?></td>
                <td>
                  <button class="btn btn-primary btn-sm" onclick="openEditModal(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($customer['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($customer['username'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($customer['password'], ENT_QUOTES); ?>')">Sửa</button>
                  <a href="customers_ad.php?delete_id=<?php echo $customer['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <!--Danh sách liên hệ-->
      <h4 style="margin-top: 40px;">Danh sách liên hệ từ người dùng</h4>
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>ID</th>
            <th>Người dùng</th>
            <th>Họ tên</th>
            <th>Email</th>
            <th>Điện thoại</th>
            <th>Nội dung</th>
            <th>Ngày gửi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $sql_contact = "SELECT c.id, u.username, c.full_name, c.email, c.phone, c.message, c.submitted_at
                    FROM contact c
                    LEFT JOIN users u ON c.user_id = u.id
                    ORDER BY c.submitted_at DESC";
          $result_contact = $conn->query($sql_contact);
          if ($result_contact && $result_contact->num_rows > 0):
            while ($row = $result_contact->fetch_assoc()):
          ?>
              <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['username'] ?? 'Khách' ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                <td><?= date("d/m/Y H:i", strtotime($row['submitted_at'])) ?></td>
              </tr>
            <?php endwhile;
          else: ?>
            <tr>
              <td colspan="7">Không có dữ liệu liên hệ.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </main>
  </div>

  <!-- Modal sửa khách hàng -->
  <div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header">
          <h5 class="modal-title" id="editCustomerModalLabel">Chỉnh sửa thông tin khách hàng</h5>
          <button type="button" class="btn-close bg-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="editCustomerForm" method="POST">
            <input type="hidden" id="editCustomerId" name="editCustomerId" />
            <div class="mb-3">
              <label class="form-label">Tên đăng nhập</label>
              <input type="text" class="form-control" id="editName" name="editName" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Email hoặc SĐT</label>
              <input type="text" class="form-control" id="editEmail" name="editEmail" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Tài khoản</label>
              <input type="text" class="form-control" id="editUsername" name="editUsername" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Mật khẩu</label>
              <input type="password" class="form-control" id="editPassword" name="editPassword" placeholder="Để trống nếu không đổi" />
              <small class="text-muted">Nhập mật khẩu mới để thay đổi, để trống để giữ nguyên.</small>
            </div>
            <input type="hidden" name="edit_customer" value="1" />
          </form>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button class="btn btn-primary" type="submit" form="editCustomerForm">Lưu thay đổi</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal thêm khách hàng -->
  <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header">
          <h5 class="modal-title" id="addCustomerModalLabel">Thêm khách hàng mới</h5>
          <button type="button" class="btn-close bg-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="addCustomerForm" method="POST">
            <div class="mb-3">
              <label class="form-label">Tên đăng nhập</label>
              <input type="text" class="form-control" id="addName" name="addName" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Email hoặc SĐT</label>
              <input type="text" class="form-control" id="addEmail" name="addEmail" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Tài khoản</label>
              <input type="text" class="form-control" id="addUsername" name="addUsername" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Mật khẩu</label>
              <input type="password" class="form-control" id="addPassword" name="addPassword" required />
            </div>
            <input type="hidden" name="add_customer" value="1" />
          </form>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button class="btn btn-success" type="submit" form="addCustomerForm">Thêm</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function openEditModal(id, name, email, username, password) {
      document.getElementById('editCustomerId').value = id;
      document.getElementById('editName').value = name;
      document.getElementById('editEmail').value = email;
      document.getElementById('editUsername').value = username;
      document.getElementById('editPassword').value = ''; // Do not pre-fill password for security
      const modal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
      modal.show();
    }
  </script>
</body>

</html>