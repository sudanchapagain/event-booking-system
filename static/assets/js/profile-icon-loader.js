document.addEventListener("DOMContentLoaded", function () {
  const profilePic = document.getElementById("profilePic");
  const dropdown = document.getElementById("profileDropdown");

  if (profilePic && dropdown) {
    profilePic.addEventListener("click", function () {
      dropdown.classList.toggle("hidden");
    });
  }
});
