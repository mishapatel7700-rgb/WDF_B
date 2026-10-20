document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("registerForm");
  if (!form) return;
  form.addEventListener("submit", (event) => {
    const password = document.getElementById("password").value;
    const confirm = document.getElementById("confirmPassword").value;
    if (password !== confirm) {
      event.preventDefault();
      alert("Passwords do not match.");
      document.getElementById("confirmPassword").focus();
    }
  });
});
