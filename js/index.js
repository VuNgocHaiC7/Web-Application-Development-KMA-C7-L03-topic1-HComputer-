document.addEventListener("DOMContentLoaded", () => {
  // Gán sự kiện cuộn để làm nổi bật header
  const header = document.getElementById("header");
  window.addEventListener("scroll", () => {
    if (window.scrollY > 50) {
      header.style.boxShadow = "0 5px 10px rgba(0,0,0,0.1)";
    } else {
      header.style.boxShadow = "none";
    }
  });

  function addToCart(productId) {
    const qty = parseInt(document.getElementById("qty").value);
    const formData = new FormData();
    formData.append("product_id", productId);
    formData.append("quantity", qty);

    fetch("api/add_to_cart.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        alert(data.message);
        if (data.success) window.location.href = "cart.php";
      });
  }

  // Gán sự kiện click cho từng sản phẩm
  const productEls = document.querySelectorAll(".pro");

  productEls.forEach((productEl) => {
    productEl.addEventListener("click", (e) => {
      const isCartBtn = e.target.classList.contains("cart");

      if (isCartBtn) {
        // Nếu click vào nút cart, kiểm tra đăng nhập
        fetch("api/check_login.php")
          .then((res) => res.json())
          .then((data) => {
            if (data.loggedIn) {
              const id = productEl.getAttribute("data-id");
              addToCart(id); // gọi hàm xử lý thêm sản phẩm
            } else {
              alert("Vui lòng đăng nhập để thêm vào giỏ hàng.");
              window.location.href = "login.php";
            }
          });
        return; // ngăn chuyển trang
      }

      // Nếu không phải nút giỏ hàng → chuyển sang trang chi tiết sản phẩm
      const id = productEl.getAttribute("data-id");
      if (id !== null) {
        window.location.href = `sproduct.php?id=${id}`;
      }
    });
  });
});

// Hàm thêm sản phẩm vào giỏ hàng
function addToCart(id) {
  let cart = JSON.parse(localStorage.getItem("cart")) || [];
  const existing = cart.find((item) => item.id === id);

  if (existing) {
    existing.qty += 1;
  } else {
    cart.push({ id, qty: 1 });
  }

  localStorage.setItem("cart", JSON.stringify(cart));
  alert("Đã thêm vào giỏ hàng!");

  // Cập nhật số lượng hiển thị
  const cartCount = document.getElementById("cart-count");
  cartCount.textContent = cart.length;
  cartCount.style.display = "inline-block";
}

// Quảng cáo animation
function initMultiBannerCarousel() {
  $(".multi-banner-carousel").owlCarousel({
    items: 2,
    margin: 20,
    loop: true,
    nav: true,
    navText: ["❮", "❯"],
    autoplay: true,
    autoplayTimeout: 1500,
    autoplayHoverPause: true,
    dots: false,
    responsive: {
      0: { items: 1 },
      768: { items: 2 },
    },
  });
}

$(document).ready(function () {
  initMultiBannerCarousel();
});
