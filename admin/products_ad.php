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
$search_type = $_GET['search_type'] ?? 'code';
$where = "WHERE p.main_img IS NOT NULL AND p.main_img != ''";
$params = [];
$types = "";

if ($search_term) {
  if ($search_type === 'code') {
    $where .= " AND p.id = ?";
    $params[] = $search_term;
    $types .= "i";
  } else {
    $where .= " AND p.name LIKE ?";
    $params[] = '%' . $search_term . '%';
    $types .= "s";
  }
}

// Lấy danh sách sản phẩm chỉ có ảnh chính, gộp mô tả
$sql = "SELECT p.*, GROUP_CONCAT(pd.description SEPARATOR ', ') AS descriptions 
        FROM products p 
        LEFT JOIN product_description pd ON p.id = pd.product_id 
        $where 
        GROUP BY p.id";
$stmt = $conn->prepare($sql);
if ($types) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Xử lý thêm sản phẩm
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
  $name = $_POST['addName'];
  $price = $_POST['addPrice'];
  $rating = $_POST['addRating'];
  $rating_count = $_POST['addReviewCount'];
  $description = $_POST['addSpecs'];

  // Xử lý ảnh chính
  $image_path = '';
  if (isset($_FILES['addImageFile']) && $_FILES['addImageFile']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../image/product/';
    $image_name = time() . '_' . basename($_FILES['addImageFile']['name']);
    $image_path = $upload_dir . $image_name;
    if (!move_uploaded_file($_FILES['addImageFile']['tmp_name'], $image_path)) {
      $error = "Tải lên hình ảnh chính thất bại.";
    }
    $image_path = 'image/product/' . $image_name; // Lưu đường dẫn tương đối
  } else {
    $error = "Vui lòng chọn hình ảnh chính.";
  }

  // Xử lý ảnh phụ
  $gallery_paths = [];
  if (isset($_FILES['addGalleryFiles']) && !$error) {
    $upload_dir = '../image/product/gallery/';
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0777, true); // Tạo thư mục nếu chưa tồn tại
    }
    foreach ($_FILES['addGalleryFiles']['name'] as $key => $name) {
      if ($_FILES['addGalleryFiles']['error'][$key] === UPLOAD_ERR_OK) {
        $gallery_name = time() . '_' . $key . '_' . basename($name);
        $gallery_path = $upload_dir . $gallery_name;
        if (move_uploaded_file($_FILES['addGalleryFiles']['tmp_name'][$key], $gallery_path)) {
          $gallery_paths[] = 'image/product/gallery/' . $gallery_name; // Lưu đường dẫn tương đối
        } else {
          $error = "Tải lên ảnh phụ $name thất bại.";
          break;
        }
      }
    }
  }

  if (!$error) {
    // Thêm sản phẩm vào bảng products
    $stmt = $conn->prepare("INSERT INTO products (name, price, main_img, rating, rating_count) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sdsii", $name, $price, $image_path, $rating, $rating_count);
    if ($stmt->execute()) {
      $product_id = $conn->insert_id;

      // Thêm mô tả vào bảng product_description
      if ($description) {
        $stmt = $conn->prepare("INSERT INTO product_description (product_id, description) VALUES (?, ?)");
        $stmt->bind_param("is", $product_id, $description);
        $stmt->execute();
      }

      // Thêm ảnh phụ vào bảng product_gallery
      if (!empty($gallery_paths)) {
        $stmt = $conn->prepare("INSERT INTO product_gallery (product_id, image_path) VALUES (?, ?)");
        foreach ($gallery_paths as $path) {
          $stmt->bind_param("is", $product_id, $path);
          $stmt->execute();
        }
      }

      header('Location: products_ad.php');
      exit();
    } else {
      $error = "Thêm sản phẩm thất bại.";
    }
  }
}

// Xử lý chỉnh sửa sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
  $id = $_POST['editId'];
  $name = $_POST['editName'];
  $price = $_POST['editPrice'];
  $rating = $_POST['editRating'];
  $rating_count = $_POST['editReviewCount'];
  $description = $_POST['editSpecs'];

  // Xử lý ảnh chính
  $image_path = $_POST['existingImage'];
  if (isset($_FILES['editImageFile']) && $_FILES['editImageFile']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../image/product/';
    $image_name = time() . '_' . basename($_FILES['editImageFile']['name']);
    $image_path = $upload_dir . $image_name;
    if (move_uploaded_file($_FILES['editImageFile']['tmp_name'], $image_path)) {
      $image_path = 'image/product/' . $image_name;
      // Xóa ảnh chính cũ nếu tồn tại
      if (file_exists('../' . $_POST['existingImage'])) {
        unlink('../' . $_POST['existingImage']);
      }
    } else {
      $error = "Tải lên hình ảnh chính thất bại.";
    }
  }

  // Xử lý ảnh phụ
  $gallery_paths = [];
  if (isset($_FILES['editGalleryFiles']) && !$error) {
    $upload_dir = '../image/product/gallery/';
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0777, true);
    }
    foreach ($_FILES['editGalleryFiles']['name'] as $key => $name) {
      if ($_FILES['editGalleryFiles']['error'][$key] === UPLOAD_ERR_OK) {
        $gallery_name = time() . '_' . $key . '_' . basename($name);
        $gallery_path = $upload_dir . $gallery_name;
        if (move_uploaded_file($_FILES['editGalleryFiles']['tmp_name'][$key], $gallery_path)) {
          $gallery_paths[] = 'image/product/gallery/' . $gallery_name;
        } else {
          $error = "Tải lên ảnh phụ $name thất bại.";
          break;
        }
      }
    }
  }

  if (!$error) {
    // Cập nhật sản phẩm
    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, main_img = ?, rating = ?, rating_count = ? WHERE id = ?");
    $stmt->bind_param("sdsiii", $name, $price, $image_path, $rating, $rating_count, $id);
    if ($stmt->execute()) {
      // Xóa mô tả cũ
      $stmt = $conn->prepare("DELETE FROM product_description WHERE product_id = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();

      // Thêm mô tả mới
      if ($description) {
        $stmt = $conn->prepare("INSERT INTO product_description (product_id, description) VALUES (?, ?)");
        $stmt->bind_param("is", $id, $description);
        $stmt->execute();
      }

      // Xóa ảnh phụ cũ
      $stmt = $conn->prepare("SELECT image_path FROM product_gallery WHERE product_id = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $old_images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      foreach ($old_images as $img) {
        if (file_exists('../' . $img['image_path'])) {
          unlink('../' . $img['image_path']);
        }
      }
      $stmt = $conn->prepare("DELETE FROM product_gallery WHERE product_id = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();

      // Thêm ảnh phụ mới
      if (!empty($gallery_paths)) {
        $stmt = $conn->prepare("INSERT INTO product_gallery (product_id, image_path) VALUES (?, ?)");
        foreach ($gallery_paths as $path) {
          $stmt->bind_param("is", $id, $path);
          $stmt->execute();
        }
      }

      header('Location: products_ad.php');
      exit();
    } else {
      $error = "Chỉnh sửa sản phẩm thất bại.";
    }
  }
}

// Xử lý xóa sản phẩm
if (isset($_GET['delete_id'])) {
  $id = $_GET['delete_id'];
  $stmt = $conn->prepare("SELECT main_img FROM products WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $image = $stmt->get_result()->fetch_assoc()['main_img'];
  if ($image && file_exists('../' . $image)) {
    unlink('../' . $image);
  }

  // Xóa ảnh phụ
  $stmt = $conn->prepare("SELECT image_path FROM product_gallery WHERE product_id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $gallery_images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  foreach ($gallery_images as $img) {
    if (file_exists('../' . $img['image_path'])) {
      unlink('../' . $img['image_path']);
    }
  }

  $stmt = $conn->prepare("DELETE FROM product_description WHERE product_id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt = $conn->prepare("DELETE FROM product_gallery WHERE product_id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
  $stmt->bind_param("i", $id);
  if ($stmt->execute()) {
    header('Location: products_ad.php');
    exit();
  } else {
    $error = "Xóa sản phẩm thất bại.";
  }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <title>Danh sách sản phẩm</title>
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

      <div class="table-responsive">
        <table class="table table-dark table-bordered align-middle text-center">
          <thead>
            <tr>
              <th>Stt</th>
              <th>Mã</th>
              <th>Tên</th>
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
                <td><?php echo number_format($product['price']); ?>đ</td>
                <td><?php echo $product['rating'] . ' (' . $product['rating_count'] . ')'; ?></td>
                <td>
                  <img src="../<?php echo htmlspecialchars($product['main_img']); ?>" alt="Product" width="50">
                </td>
                <td><?php echo htmlspecialchars($product['descriptions'] ?? 'Không có'); ?></td>
                <td>
                  <button class="btn btn-primary btn-sm" onclick="openEditModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>', '<?php echo $product['price']; ?>', '<?php echo $product['rating']; ?>', '<?php echo $product['rating_count']; ?>', '<?php echo htmlspecialchars($product['main_img'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($product['descriptions'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars(json_encode(array_column($gallery_images, 'image_path')), ENT_QUOTES); ?>')">Sửa</button>
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
                <label class="form-label">Giá tiền:</label>
                <input type="number" class="form-control" id="editPrice" name="editPrice" step="0.01" required />
              </div>
              <div class="col">
                <label class="form-label">Số sao (1–5):</label>
                <input type="number" min="1" max="5" class="form-control" id="editRating" name="editRating" required />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Số lượt đánh giá:</label>
              <input type="number" min="0" class="form-control" id="editReviewCount" name="editReviewCount" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Hình ảnh chính:</label>
              <input type="file" class="form-control" id="editImageFile" name="editImageFile" />
              <img id="editImagePreview" src="" alt="Preview" class="mt-2" style="max-width: 100px; display: none;" />
            </div>
            <div class="mb-3">
              <label class="form-label">Ảnh phụ (tối đa 4 ảnh):</label>
              <input type="file" class="form-control mb-2" name="editGalleryFiles[]" accept="image/*" />
              <input type="file" class="form-control mb-2" name="editGalleryFiles[]" accept="image/*" />
              <input type="file" class="form-control mb-2" name="editGalleryFiles[]" accept="image/*" />
              <input type="file" class="form-control mb-2" name="editGalleryFiles[]" accept="image/*" />
            </div>
            <div class="mb-3">
              <label class="form-label">Thông số kỹ thuật:</label>
              <textarea class="form-control" id="editSpecs" name="editSpecs" rows="3"></textarea>
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
                <label class="form-label">Giá tiền:</label>
                <input type="number" class="form-control" id="addPrice" name="addPrice" step="0.01" required />
              </div>
              <div class="col">
                <label class="form-label">Số sao (1–5):</label>
                <input type="number" min="1" max="5" class="form-control" id="addRating" name="addRating" required />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Số lượt đánh giá:</label>
              <input type="number" min="0" class="form-control" id="addReviewCount" name="addReviewCount" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Hình ảnh chính:</label>
              <input type="file" class="form-control" id="addImageFile" name="addImageFile" required />
            </div>
            <div class="mb-3">
              <label class="form-label">Ảnh phụ (tối đa 4 ảnh):</label>
              <input type="file" class="form-control mb-2" name="addGalleryFiles[]" accept="image/*" />
              <input type="file" class="form-control mb-2" name="addGalleryFiles[]" accept="image/*" />
              <input type="file" class="form-control mb-2" name="addGalleryFiles[]" accept="image/*" />
              <input type="file" class="form-control mb-2" name="addGalleryFiles[]" accept="image/*" />
            </div>
            <div class="mb-3">
              <label class="form-label">Thông số kỹ thuật:</label>
              <textarea class="form-control" id="addSpecs" name="addSpecs" rows="3"></textarea>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function openEditModal(id, name, price, rating, rating_count, image, specs, gallery) {
      // Xóa ảnh phụ hiện tại trong modal để tránh trùng lặp
      document.querySelectorAll('#editProductModal img.mt-2:not(#editImagePreview)').forEach(img => img.remove());

      document.getElementById('editId').value = id;
      document.getElementById('editCode').value = id;
      document.getElementById('editName').value = name;
      document.getElementById('editPrice').value = price;
      document.getElementById('editRating').value = rating;
      document.getElementById('editReviewCount').value = rating_count;
      document.getElementById('editSpecs').value = specs;
      document.getElementById('existingImage').value = image;
      const preview = document.getElementById('editImagePreview');
      if (image) {
        preview.src = '../' + image;
        preview.style.display = 'block';
      } else {
        preview.style.display = 'none';
      }
      // Hiển thị ảnh phụ
      const galleryImages = JSON.parse(gallery);
      const galleryInputs = document.querySelectorAll('input[name="editGalleryFiles[]"]');
      galleryImages.forEach((img, index) => {
        if (index < galleryInputs.length) {
          const previewImg = document.createElement('img');
          previewImg.src = '../' + img;
          previewImg.style.maxWidth = '100px';
          previewImg.className = 'mt-2';
          galleryInputs[index].insertAdjacentElement('afterend', previewImg);
        }
      });
      const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
      modal.show();
    }
  </script>
</body>

</html>