document.getElementById("contactForm").addEventListener("submit", function (e) {
  e.preventDefault(); // Ngăn submit form mặc định

  const name = document.getElementById("name").value.trim();
  const phone = document.getElementById("phone").value.trim();
  const email = document.getElementById("email").value.trim();
  const subject = document.getElementById("subject").value.trim();
  const message = document.getElementById("message").value.trim();

  if (!name || !phone || !email || !subject || !message) {
    alert("Vui lòng điền đầy đủ tất cả các trường bắt buộc!");
    return;
  }

  alert("Thông tin đã được gửi thành công! (giả lập)");
  // Nếu cần gửi đi thật, bạn có thể gọi API hoặc xử lý tiếp tại đây
});
