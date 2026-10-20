// Authentication is handled by login.php and the database, not browser localStorage.
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("loginForm");
  if (form) {
    form.addEventListener("submit", (event) => {
      const email = document.getElementById("email").value.trim();
      const password = document.getElementById("password").value;
      if (!email || !password) {
        event.preventDefault();
        alert("Please enter your email and password.");
      }
    });
  }
});
