function showForm(formType) {
  const loginForm = document.getElementById("login-form");
  const registerForm = document.getElementById("register-form");
  const tabs = document.querySelectorAll(".tab");

  if (formType === "login") {
    loginForm.classList.add("active");
    registerForm.classList.remove("active");
    tabs[0].classList.add("active");
    tabs[1].classList.remove("active");
  } else {
    loginForm.classList.remove("active");
    registerForm.classList.add("active");
    tabs[0].classList.remove("active");
    tabs[1].classList.add("active");
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("login-form");
  const registerForm = document.getElementById("register-form");

  // Hiển thị lỗi
  function showError(input, message) {
    let errorEl = input.nextElementSibling;
    if (!errorEl || !errorEl.classList.contains("error-message")) {
      errorEl = document.createElement("div");
      errorEl.className = "error-message";
      input.parentNode.insertBefore(errorEl, input.nextSibling);
    }
    errorEl.textContent = message;
  }

  // Xóa lỗi
  function clearError(input) {
    const errorEl = input.nextElementSibling;
    if (errorEl && errorEl.classList.contains("error-message")) {
      errorEl.remove();
    }
  }

  // Xử lý đăng nhập
  loginForm.addEventListener("submit", function (e) {
    const username = document.getElementById("login-username");
    const password = document.getElementById("login-password");

    let valid = true;

    if (username.value.trim() === "") {
      showError(username, "Vui lòng nhập tên đăng nhập.");
      valid = false;
    } else {
      clearError(username);
    }

    if (password.value.trim() === "") {
      showError(password, "Vui lòng nhập mật khẩu.");
      valid = false;
    } else {
      clearError(password);
    }

    if (!valid) {
      e.preventDefault(); // Chỉ chặn nếu có lỗi
    }
    // Nếu hợp lệ thì form tự động gửi về api/login.php
  });

  // Xử lý đăng ký
  registerForm.addEventListener("submit", function (e) {
    const fullname = document.getElementById("register-fullname");
    const email = document.getElementById("register-email");
    const password = document.getElementById("register-password");
    const confirm = document.getElementById("confirm-password");

    let valid = true;

    if (fullname.value.trim() === "") {
      showError(fullname, "Vui lòng nhập họ tên.");
      valid = false;
    } else {
      clearError(fullname);
    }

    if (email.value.trim() === "") {
      showError(email, "Vui lòng nhập email hoặc số điện thoại.");
      valid = false;
    } else {
      clearError(email);
    }

    if (password.value.trim() === "") {
      showError(password, "Vui lòng nhập mật khẩu.");
      valid = false;
    } else {
      clearError(password);
    }

    if (confirm.value.trim() === "") {
      showError(confirm, "Vui lòng xác nhận mật khẩu.");
      valid = false;
    } else if (confirm.value !== password.value) {
      showError(confirm, "Mật khẩu xác nhận không khớp.");
      valid = false;
    } else {
      clearError(confirm);
    }

    if (!valid) {
      e.preventDefault(); // Chặn nếu có lỗi
    }
    // Nếu hợp lệ thì form sẽ tự gửi (ví dụ đến action="api/register.php")
  });
});
document
  .getElementById("login-form")
  .addEventListener("submit", async function (e) {
    e.preventDefault();
    const username = document.getElementById("login-username").value;
    const password = document.getElementById("login-password").value;

    const response = await fetch("api/login.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `username=${encodeURIComponent(
        username
      )}&password=${encodeURIComponent(password)}`,
    });

    const result = await response.json();
    alert(result.message);

    if (result.status === "admin") {
      window.location.href = "admin/admin.php";
    } else if (result.status === "user") {
      window.location.href = "index.php";
    }
  });
