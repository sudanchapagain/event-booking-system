document
  .getElementById("loginForm")
  .addEventListener("submit", function (event) {
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const passwordRegex =
      /^(?=.*[!@#$%^&*(),.?":{}|<>])(?=.*\d)(?=.*[a-z])(?=.*[A-Z])/;

    let valid = true;

    clearErrors();

    if (!emailRegex.test(email)) {
      showError("emailError", "Invalid email format");
      valid = false;
    }

    if (password.length < 8) {
      showError("passwordError", "Password must be at least 8 characters long");
      valid = false;
    } else if (!passwordRegex.test(password)) {
      showError(
        "passwordError",
        "Password must contain at least one special character, one digit, one uppercase letter, and one lowercase letter",
      );
      valid = false;
    }

    if (!valid) {
      event.preventDefault();
    } else {
      document.getElementById("submitError").textContent =
        "Logging in, please wait...";
    }
  });

function clearErrors() {
  document.getElementById("emailError").textContent = "";
  document.getElementById("passwordError").textContent = "";
  document.getElementById("submitError").textContent = "";
}

function showError(elementId, message) {
  document.getElementById(elementId).textContent = message;
}
