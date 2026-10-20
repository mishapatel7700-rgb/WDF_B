document.addEventListener("DOMContentLoaded", () => {
  const button = document.getElementById("themeBtn");
  if (!button) return;
  const apply = (dark) => {
    document.body.classList.toggle("dark-mode", dark);
    button.textContent = dark ? "Light Mode" : "Dark Mode";
  };
  let dark = false;
  try { dark = localStorage.getItem("charusatTheme") === "dark"; } catch (_) {}
  apply(dark);
  button.addEventListener("click", () => {
    dark = !document.body.classList.contains("dark-mode");
    apply(dark);
    try { localStorage.setItem("charusatTheme", dark ? "dark" : "light"); } catch (_) {}
  });
});
