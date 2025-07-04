<?php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Kiểm tra quyền admin
if (empty($_SESSION['admin'])) {
  $_SESSION['error_message'] = "Bạn cần đăng nhập với tài khoản admin để truy cập.";
  header('Location: ../login.php');
  exit();
}

// --- XỬ LÝ TÌM KIẾM ---
$search_term = trim($_GET['search'] ?? '');
$search_type = ($_GET['search_type'] ?? 'code') === 'name' ? 'name' : 'code';
$where = "WHERE p.main_img != ''";
$params = [];
$types = '';

if ($search_term !== '') {
  if ($search_type === 'code') {
    $where   .= " AND p.id = ?";
    $params[] = (int)$search_term;
    $types   .= 'i';
  } else {
    $where   .= " AND p.name LIKE ?";
    $params[] = "%{$search_term}%";
    $types   .= 's';
  }
}

$sql = "
    SELECT
        p.*, 
        GROUP_CONCAT(pd.description SEPARATOR ', ') AS descriptions
    FROM products p
    LEFT JOIN product_description pd ON p.id = pd.product_id
    {$where}
    GROUP BY p.id
";
$stmt = $conn->prepare($sql);
if ($types !== '') {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Biến lưu lỗi/thành công
$error   = '';
$success = '';

// Helper: upload file
function uploadFile(array $file, string $uploadDir, string &$error): string
{
  if ($file['error'] !== UPLOAD_ERR_OK) {
    $error = "Vui lòng chọn file hoặc lỗi upload.";
    return '';
  }
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
  $name   = time() . '_' . basename($file['name']);
  $target = rtrim($uploadDir, '/') . '/' . $name;
  if (move_uploaded_file($file['tmp_name'], $target)) {
    return $target;
  }
  $error = "Tải lên file {$file['name']} thất bại.";
  return '';
}

// Xử lý thêm sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
  $name          = trim($_POST['addName'] ?? '');
  $type          = trim($_POST['addType'] ?? '');
  $price         = floatval($_POST['addPrice'] ?? 0);
  $rating        = intval($_POST['addRating'] ?? 0);
  $rating_count  = intval($_POST['addReviewCount'] ?? 0);
  $brand         = trim($_POST['addBrand'] ?? '');
  $descriptions  = trim($_POST['addSpecs'] ?? '');

  // Validate cơ bản
  if (empty($name) || empty($type) || $price < 0 || $rating < 1 || $rating > 5 || $rating_count < 0) {
    $error = "Dữ liệu đầu vào không hợp lệ. Vui lòng kiểm tra lại.";
  } else {
    // 1) Xử lý ảnh chính
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size      = 5 * 1024 * 1024;
    $image_path    = '';
    if (isset($_FILES['addImageFile']) && $_FILES['addImageFile']['error'] === UPLOAD_ERR_OK) {
      if (
        in_array($_FILES['addImageFile']['type'], $allowed_types) &&
        $_FILES['addImageFile']['size'] <= $max_size
      ) {
        $upload_dir  = __DIR__ . '/../image/product/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $image_name  = time() . '_' . basename($_FILES['addImageFile']['name']);
        $full_target = $upload_dir . $image_name;
        if (move_uploaded_file($_FILES['addImageFile']['tmp_name'], $full_target)) {
          $image_path = 'image/product/' . $image_name;
        } else {
          $error = "Tải lên hình ảnh chính thất bại.";
        }
      } else {
        $error = "Hình ảnh chính không hợp lệ (JPEG/PNG/GIF, ≤5MB).";
      }
    } else {
      $error = "Vui lòng chọn hình ảnh chính.";
    }

    // 2) Xử lý ảnh phụ (tối đa 4)
    $gallery_paths = [];
    if (!$error && !empty($_FILES['addGalleryFiles']['name'][0])) {
      $upload_dir = __DIR__ . '/../image/product/gallery/';
      if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

      foreach ($_FILES['addGalleryFiles']['name'] as $key => $fileName) {
        if (
          $_FILES['addGalleryFiles']['error'][$key] === UPLOAD_ERR_OK &&
          count($gallery_paths) < 4
        ) {

          if (
            in_array($_FILES['addGalleryFiles']['type'][$key], $allowed_types) &&
            $_FILES['addGalleryFiles']['size'][$key] <= $max_size
          ) {

            $gallery_name = time() . '_' . $key . '_' . basename($fileName);
            $full_target  = $upload_dir . $gallery_name;

            if (move_uploaded_file($_FILES['addGalleryFiles']['tmp_name'][$key], $full_target)) {
              $gallery_paths[] = 'image/product/gallery/' . $gallery_name;
            } else {
              $error = "Tải lên ảnh phụ {$fileName} thất bại.";
              break;
            }
          } else {
            $error = "Ảnh phụ {$fileName} không hợp lệ.";
            break;
          }
        }
      }
    }

    // 3) Lưu vào DB nếu không có lỗi
    if (!$error) {
      $stmt = $conn->prepare("
                INSERT INTO products
                    (name, type, price, main_img, brand, rating, rating_count)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
      $stmt->bind_param("ssdssii", $name, $type, $price, $image_path, $brand, $rating, $rating_count);
      if ($stmt->execute()) {
        $product_id = $conn->insert_id;

        // mô tả
        if ($descriptions !== '') {
          $stmt = $conn->prepare("
                        INSERT INTO product_description (product_id, description)
                        VALUES (?, ?)
                    ");
          $stmt->bind_param("is", $product_id, $descriptions);
          $stmt->execute();
        }

        // gallery
        if ($gallery_paths) {
          $stmt = $conn->prepare("
                        INSERT INTO product_gallery (product_id, image_path)
                        VALUES (?, ?)
                    ");
          foreach ($gallery_paths as $path) {
            $stmt->bind_param("is", $product_id, $path);
            $stmt->execute();
          }
        }

        header('Location: products_ad.php?success=1');
        exit();
      } else {
        $error = "Thêm sản phẩm thất bại: " . $conn->error;
      }
    }
  }
}


// Xử lý chỉnh sửa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
  $id           = intval($_POST['editId'] ?? 0);
  $name         = trim($_POST['editName'] ?? '');
  $type         = trim($_POST['editType'] ?? '');
  $price        = floatval($_POST['editPrice'] ?? 0);
  $rating       = intval($_POST['editRating'] ?? 0);
  $rating_count = intval($_POST['editReviewCount'] ?? 0);
  $brand        = trim($_POST['editBrand'] ?? '');
  $descriptions = trim($_POST['editSpecs'] ?? '');
  $existingImg  = trim($_POST['existingImage'] ?? '');

  // Validate
  if (
    $id <= 0 || empty($name) || empty($type) || $price < 0 ||
    $rating < 1 || $rating > 5 || $rating_count < 0
  ) {
    $error = "Dữ liệu đầu vào không hợp lệ.";
  } else {
    // 1) Ảnh chính
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size      = 5 * 1024 * 1024;
    $image_path    = $existingImg;

    if (!empty($_FILES['editImageFile']['tmp_name'])) {
      if (
        in_array($_FILES['editImageFile']['type'], $allowed_types) &&
        $_FILES['editImageFile']['size'] <= $max_size
      ) {

        $upload_dir  = __DIR__ . '/../image/product/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $image_name  = time() . '_' . basename($_FILES['editImageFile']['name']);
        $full_target = $upload_dir . $image_name;

        if (move_uploaded_file($_FILES['editImageFile']['tmp_name'], $full_target)) {
          // xóa file cũ
          @unlink(__DIR__ . '/../' . $existingImg);
          $image_path = 'image/product/' . $image_name;
        } else {
          $error = "Tải lên hình ảnh chính thất bại.";
        }
      } else {
        $error = "Hình ảnh chính không hợp lệ.";
      }
    }

    // 2) Ảnh phụ (xóa cũ rồi thêm mới)
    $gallery_paths = [];
    if (!$error && !empty($_FILES['editGalleryFiles']['name'][0])) {
      $upload_dir = __DIR__ . '/../image/product/gallery/';
      if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

      // xóa gallery cũ
      $stmt = $conn->prepare("
                SELECT image_path 
                FROM product_gallery 
                WHERE product_id = ?
            ");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
        @unlink(__DIR__ . '/../' . $row['image_path']);
      }
      $conn->query("DELETE FROM product_gallery WHERE product_id = $id");

      // upload mới
      foreach ($_FILES['editGalleryFiles']['name'] as $key => $fileName) {
        if (
          $_FILES['editGalleryFiles']['error'][$key] === UPLOAD_ERR_OK &&
          count($gallery_paths) < 4
        ) {

          if (
            in_array($_FILES['editGalleryFiles']['type'][$key], $allowed_types) &&
            $_FILES['editGalleryFiles']['size'][$key] <= $max_size
          ) {

            $gallery_name = time() . '_' . $key . '_' . basename($fileName);
            $full_target  = $upload_dir . $gallery_name;

            if (move_uploaded_file($_FILES['editGalleryFiles']['tmp_name'][$key], $full_target)) {
              $gallery_paths[] = 'image/product/gallery/' . $gallery_name;
            } else {
              $error = "Tải lên ảnh phụ {$fileName} thất bại.";
              break;
            }
          } else {
            $error = "Ảnh phụ {$fileName} không hợp lệ.";
            break;
          }
        }
      }
    }

    // 3) Cập nhật DB
    if (!$error) {
      $stmt = $conn->prepare("
                UPDATE products 
                SET name=?, type=?, price=?, main_img=?, brand=?, rating=?, rating_count=?
                WHERE id=?
            ");
      $stmt->bind_param(
        "ssdssiii",
        $name,
        $type,
        $price,
        $image_path,
        $brand,
        $rating,
        $rating_count,
        $id
      );
      if ($stmt->execute()) {
        // cập nhật mô tả
        $conn->query("DELETE FROM product_description WHERE product_id = $id");
        if ($descriptions !== '') {
          $stmt = $conn->prepare("
                        INSERT INTO product_description (product_id, description)
                        VALUES (?, ?)
                    ");
          $stmt->bind_param("is", $id, $descriptions);
          $stmt->execute();
        }
        // cập nhật gallery
        if ($gallery_paths) {
          $stmt = $conn->prepare("
                        INSERT INTO product_gallery (product_id, image_path)
                        VALUES (?, ?)
                    ");
          foreach ($gallery_paths as $path) {
            $stmt->bind_param("is", $id, $path);
            $stmt->execute();
          }
        }
        header('Location: products_ad.php?updated=1');
        exit();
      } else {
        $error = "Chỉnh sửa thất bại: " . $conn->error;
      }
    }
  }
}

// --- XỬ LÝ XÓA SẢN PHẨM ---
if (isset($_GET['delete_id'])) {
  $id = intval($_GET['delete_id']);
  // xóa ảnh chính
  $stmt = $conn->prepare("SELECT main_img FROM products WHERE id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $img = $stmt->get_result()->fetch_assoc()['main_img'];
  @unlink(__DIR__ . '/../' . $img);

  // xóa gallery
  $stmt = $conn->prepare("SELECT image_path FROM product_gallery WHERE product_id = ?");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
    @unlink(__DIR__ . '/../' . $row['image_path']);
  }
  $conn->query("DELETE FROM product_gallery WHERE product_id = $id");

  // xóa mô tả và bản ghi
  $conn->query("DELETE FROM product_description WHERE product_id = $id");
  $conn->query("DELETE FROM products WHERE id = $id");

  header('Location: products_ad.php?deleted=1');
  exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>Danh sách sản phẩm</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
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
          <a class="nav-link active bg-info rounded" href="products_ad.php"><i class="fas fa-box me-2"></i>Sản Phẩm</a>
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
    <main class="flex-grow-1 p-4">
      <h4 class="mb-4">Danh sách sản phẩm</h4>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Thêm sản phẩm thành công.</div>
      <?php endif; ?>
      <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success">Chỉnh sửa sản phẩm thành công.</div>
      <?php endif; ?>
      <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Xóa sản phẩm thành công.</div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-dark table-bordered align-middle text-center">
          <thead>
            <tr>
              <th>Stt</th>
              <th>Mã</th>
              <th>Tên</th>
              <th>Loại</th>
              <th>Thương hiệu</th>
              <th>Giá</th>
              <th>Đánh giá</th>
              <th>Hình ảnh</th>
              <th>Mô tả</th>
              <th>Hành động</th>
            </tr>
          </thead>
          <tbody id="product-table-body">
            <?php foreach ($products as $index => $product): ?>
              <?php
              // Lấy danh sách ảnh phụ
              $stmt = $conn->prepare("SELECT image_path FROM product_gallery WHERE product_id = ?");
              $stmt->bind_param("i", $product['id']);
              $stmt->execute();
              $gallery_images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
              ?>
              <tr>
                <td><?php echo $index + 1; ?></td>
                <td><?php echo $product['id']; ?></td>
                <td><?php echo htmlspecialchars($product['name']); ?></td>
                <td><?php echo htmlspecialchars($product['type'] ?? 'Không có'); ?></td>
                <td><?php echo htmlspecialchars($product['brand'] ?? 'Không có'); ?></td>
                <td><?php echo number_format($product['price']); ?>đ</td>
                <td><?php echo $product['rating'] . ' (' . $product['rating_count'] . ')'; ?></td>
                <td>
                  <img src="../<?php echo htmlspecialchars($product['main_img']); ?>" alt="Product" width="50">
                </td>
                <td><?php echo htmlspecialchars($product['descriptions'] ?? 'Không có'); ?></td>
                <td>
                  <button class="btn btn-primary btn-sm" onclick="openEditModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($product['type'], ENT_QUOTES); ?>', '<?php echo $product['price']; ?>', '<?php echo $product['rating']; ?>', '<?php echo $product['rating_count']; ?>', '<?php echo htmlspecialchars($product['brand'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($product['main_img'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($product['descriptions'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars(json_encode(array_column($gallery_images, 'image_path')), ENT_QUOTES); ?>')">Sửa</button>
                  <a href="products_ad.php?delete_id=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa?')">Xóa</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <form method="GET" class="d-flex align-items-center gap-2">
        <select class="form-select w-auto" name="search_type">
          <option value="code" <?php echo $search_type === 'code' ? 'selected' : ''; ?>>Tìm theo mã</option>
          <option value="name" <?php echo $search_type === 'name' ? 'selected' : ''; ?>>Tìm theo tên</option>
        </select>
        <input type="text" class="form-control w-25" name="search" placeholder="Tìm kiếm..." value="<?php echo htmlspecialchars($search_term); ?>" />
        <button type="submit" class="btn btn-dark"><i class="fas fa-search"></i> Tìm</button>
        <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#addProductModal">
          <i class="fas fa-plus"></i> Thêm sản phẩm
        </button>
      </form>
    </main>
  </div>

  <!-- Modal thêm sản phẩm -->
  <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header">
          <h5 class="modal-title" id="addProductModalLabel">Thêm sản phẩm mới</h5>
          <button type="button" class="btn-close bg-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="addProductForm" enctype="multipart/form-data" method="POST">
            <div class="row mb-3">
              <div class="col">
                <label class="form-label">Mã sản phẩm:</label>
                <input type="text" class="form-control" id="addCode" name="addCode" readonly value="Tự động" />
              </div>
              <div class="col">
                <label class="form-label">Tên sản phẩm:</label>
                <input type="text" class="form-control" id="addName" name="addName" required />
              </div>
            </div>
            <div class="row mb-3">
              <div class="col">
                <label class="form-label">Loại sản phẩm (type):</label>
                <input type="text" class="form-control" id="addType" name="addType" required placeholder="Ví dụ: Camera, Điện thoại" />
              </div>
              <div class="col">
                <label class="form-label">Thương hiệu:</label>
                <input type="text" class="form-control" id="addBrand" name="addBrand" placeholder="Ví dụ: Samsung, ASUS" />
              </div>
            </div>
            <div class="row mb-3">
              <div class="col">
                <label class="form-label">Giá tiền:</label>
                <input type="number" class="form-control" id="addPrice" name="addPrice" step="0.01" min="0" required />
              </div>
              <div class="col">
                <label class="form-label">Số sao (1–5):</label>
                <input type="number" min="1" max="5" class="form-control" id="addRating" name="addRating" required />
              </div>
            </div>
            <div class="row mb-3">
              <div class="col">
                <label class="form-label">Số lượt đánh giá:</label>
                <input type="number" min="0" class="form-control" id="addReviewCount" name="addReviewCount" required />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Hình ảnh chính:</label>
              <input type="file" class="form-control" id="addImageFile" name="addImageFile" accept="image/jpeg,image/png,image/gif" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Ảnh phụ (tối đa 4 ảnh):</label>
              <input type="file" class="form-control mb-2" name="addGalleryFiles[]" accept="image/jpeg,image/png,image/gif" />
              <input type="file" class="form-control mb-2" name="addGalleryFiles[]" accept="image/jpeg,image/png,image/gif" />
              <input type="file" class="form-control mb-2" name="addGalleryFiles[]" accept="image/jpeg,image/png,image/gif" />
              <input type="file" class="form-control mb-2" name="addGalleryFiles[]" accept="image/jpeg,image/png,image/gif" />
            </div>
            <div class="mb-3">
              <label class="form-label">Thông số kỹ thuật:</label>
              <input type="text" class="form-control" id="addSpecs" name="addSpecs" placeholder="Ví dụ: Độ phân giải: 5 MP; Góc nhìn: 360 độ;" />
            </div>
            <input type="hidden" name="add_product" value="1" />
          </form>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button class="btn btn-success" type="submit" form="addProductForm">Thêm sản phẩm</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal chỉnh sửa sản phẩm -->
  <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header">
          <h5 class="modal-title" id="editProductModalLabel">Chỉnh sửa sản phẩm</h5>
          <button type="button" class="btn-close bg-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="editProductForm" enctype="multipart/form-data" method="POST">
            <input type="hidden" id="editId" name="editId" />
            <input type="hidden" id="existingImage" name="existingImage" />
            <div class="row mb-3">
              <div class="col">
                <label class="form-label">Mã sản phẩm:</label>
                <input type="text" class="form-control" id="editCode" name="editCode" readonly />
              </div>
              <div class="col">
                <label class="form-label">Tên sản phẩm:</label>
                <input type="text" class="form-control" id="editName" name="editName" required />
              </div>
            </div>
            <div class="row mb-3">
              <div class="col">
                <label class="form-label">Loại sản phẩm (type):</label>
                <input type="text" class="form-control" id="editType" name="editType" required placeholder="Ví dụ: Camera, Điện thoại" />
              </div>
              <div class="col">
                <label class="form-label">Thương hiệu:</label>
                <input type="text" class="form-control" id="editBrand" name="editBrand" placeholder="Ví dụ: Samsung, ASUS" />
              </div>
            </div>
            <div class="row mb-3">
              <div class="col">
                <label class="form-label">Giá tiền:</label>
                <input type="number" class="form-control" id="editPrice" name="editPrice" step="0.01" min="0" required />
              </div>
              <div class="col">
                <label class="form-label">Số sao (1–5):</label>
                <input type="number" min="1" max="5" class="form-control" id="editRating" name="editRating" required />
              </div>
            </div>
            <div class="row mb-3">
              <div class="col">
                <label class="form-label">Số lượt đánh giá:</label>
                <input type="number" min="0" class="form-control" id="editReviewCount" name="editReviewCount" required />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Hình ảnh chính:</label>
              <input type="file" class="form-control" id="editImageFile" name="editImageFile" accept="image/jpeg,image/png,image/gif" />
              <img id="editImagePreview" src="" alt="Preview" class="mt-2" style="max-width: 100px; display: none;" />
            </div>
            <div class="mb-3">
              <label class="form-label">Ảnh phụ (tối đa 4 ảnh):</label>
              <input type="file" class="form-control mb-2" name="editGalleryFiles[]" accept="image/jpeg,image/png,image/gif" />
              <input type="file" class="form-control mb-2" name="editGalleryFiles[]" accept="image/jpeg,image/png,image/gif" />
              <input type="file" class="form-control mb-2" name="editGalleryFiles[]" accept="image/jpeg,image/png,image/gif" />
              <input type="file" class="form-control mb-2" name="editGalleryFiles[]" accept="image/jpeg,image/png,image/gif" />
            </div>
            <div class="mb-3">
              <label class="form-label">Thông số kỹ thuật:</label>
              <input type="text" class="form-control" id="editSpecs" name="editSpecs" placeholder="Ví dụ: Độ phân giải: 5 MP; Góc nhìn: 360 độ;" />
            </div>
            <input type="hidden" name="edit_product" value="1" />
          </form>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button class="btn btn-primary" type="submit" form="editProductForm">Lưu thay đổi</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function openEditModal(id, name, type, price, rating, rating_count, brand, image, specs, gallery) {
      document.getElementById('editProductForm').reset();
      document.querySelectorAll('#editProductModal img.mt-2:not(#editImagePreview)').forEach(img => img.remove());

      document.getElementById('editId').value = id;
      document.getElementById('editCode').value = id;
      document.getElementById('editName').value = name;
      document.getElementById('editType').value = type;
      document.getElementById('editPrice').value = price;
      document.getElementById('editRating').value = rating;
      document.getElementById('editReviewCount').value = rating_count;
      document.getElementById('editBrand').value = brand;
      document.getElementById('existingImage').value = image;
      document.getElementById('editSpecs').value = specs;

      const preview = document.getElementById('editImagePreview');
      if (image) {
        preview.src = '../' + image;
        preview.style.display = 'block';
      } else {
        preview.style.display = 'none';
      }

      const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
      modal.show();
    }
  </script>
</body>

</html>