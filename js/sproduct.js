document.addEventListener("DOMContentLoaded", function () {
  const mainImg = document.getElementById("MainImg");
  const thumbs = document.querySelectorAll(".small-img");
  thumbs.forEach((img) => {
    img.onclick = () => (mainImg.src = img.src);
  });

  // Lấy trạng thái đăng nhập từ thuộc tính data
  const isLoggedIn =
    document.getElementById("add-to-cart-form").dataset.isLoggedIn === "true";

  // Gắn sự kiện submit cho form
  const addToCartForm = document.getElementById("add-to-cart-form");
  if (addToCartForm) {
    addToCartForm.addEventListener("submit", function (event) {
      // Kiểm tra nếu người dùng chưa đăng nhập
      if (!isLoggedIn) {
        event.preventDefault(); // Ngăn form submit
        alert("Bạn cần đăng nhập để thêm sản phẩm vào giỏ hàng.");
        window.location.href = "login.php"; // Chuyển hướng đến trang đăng nhập
      }

      // Kiểm tra số lượng hợp lệ
      const qty = parseInt(document.getElementById("qty").value);
      if (isNaN(qty) || qty < 1) {
        event.preventDefault(); // Ngăn form submit
        alert("Vui lòng nhập số lượng hợp lệ.");
      }
    });
  }
});
