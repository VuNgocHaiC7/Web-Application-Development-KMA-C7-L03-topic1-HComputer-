document.addEventListener("DOMContentLoaded", function () {
  const filterForm = document.getElementById("filter-form");
  const searchBtn = document.getElementById("search-btn");
  const searchInput = document.getElementById("search-input");

  // Gửi form khi nhấn nút tìm kiếm
  if (searchBtn) {
    searchBtn.addEventListener("click", function (e) {
      e.preventDefault();
      filterForm.submit();
    });
  }

  // Gửi form khi nhấn phím Enter trong ô tìm kiếm
  if (searchInput) {
    searchInput.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        filterForm.submit();
      }
    });
  }
});
