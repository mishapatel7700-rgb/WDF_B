const container = document.getElementById("eventsContainer");
const searchInput = document.getElementById("searchInput");
const categoryFilter = document.getElementById("categoryFilter");
const sortSelect = document.getElementById("sortSelect");
const resultCount = document.getElementById("resultCount");
const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");
const pageInfo = document.getElementById("pageInfo");

const modal = document.getElementById("registrationModal");
const form = document.getElementById("registrationForm");
const eventIdInput = document.getElementById("eventId");
const selectedEvent = document.getElementById("selectedEvent");
const registrationMessage = document.getElementById("registrationMessage");

let allEvents = [];
let filteredEvents = [];
let currentPage = 1;
const eventsPerPage = 4;

async function loadEvents() {
  try {
    const response = await fetch("events.php");
    if (!response.ok) throw new Error("Could not load events.");
    allEvents = await response.json();
    if (!Array.isArray(allEvents)) throw new Error("Invalid event data.");

    const categories = [...new Set(allEvents.map(event => event.category))].sort();
    categories.forEach(category => {
      const option = document.createElement("option");
      option.value = category;
      option.textContent = category;
      categoryFilter.appendChild(option);
    });

    applyFilters();
  } catch (error) {
    resultCount.textContent = "";
    container.innerHTML = '<div class="no-results"><h3>Unable to load events</h3><p>Check that Apache and MySQL are running and that the database was imported.</p></div>';
  }
}

function safeText(value) {
  return String(value ?? "");
}

function displayEvents() {
  container.replaceChildren();
  const totalPages = Math.ceil(filteredEvents.length / eventsPerPage);
  if (totalPages && currentPage > totalPages) currentPage = totalPages;
  const start = (currentPage - 1) * eventsPerPage;

  filteredEvents.slice(start, start + eventsPerPage).forEach(event => {
    const card = document.createElement("article");
    card.className = "card";

    const image = document.createElement("img");
    image.className = "card-img";
    image.src = event.image;
    image.alt = event.title;
    image.loading = "lazy";
    image.onerror = () => { image.src = "images/event-default.svg"; };

    const body = document.createElement("div");
    body.className = "card-body";

    const category = document.createElement("span");
    category.className = "card-category";
    category.textContent = event.category;

    const title = document.createElement("h3");
    title.className = "card-title";
    title.textContent = event.title;

    const date = document.createElement("div");
    date.className = "card-meta";
    date.textContent = new Date(`${event.date}T00:00:00`).toLocaleDateString("en-IN", {
      day: "2-digit", month: "long", year: "numeric"
    });

    const description = document.createElement("p");
    description.className = "card-text";
    description.textContent = event.description;

    const button = document.createElement("button");
    button.className = "primary-btn register-btn";
    button.type = "button";
    button.textContent = "Register Now";
    button.addEventListener("click", () => openRegistration(event));

    body.append(category, title, date, description, button);
    card.append(image, body);
    container.appendChild(card);
  });

  if (!filteredEvents.length) {
    container.innerHTML = '<div class="no-results"><h3>No events found</h3><p>Try changing your search or category.</p></div>';
  }

  resultCount.textContent = `${filteredEvents.length} event(s) found`;
  pageInfo.textContent = `Page ${totalPages ? currentPage : 0} of ${totalPages}`;
  prevBtn.disabled = currentPage <= 1;
  nextBtn.disabled = currentPage >= totalPages || totalPages === 0;
}

function applyFilters() {
  const term = searchInput.value.toLowerCase().trim();
  const category = categoryFilter.value;
  filteredEvents = allEvents.filter(event => {
    const matchesText = [event.title, event.description, event.category]
      .some(value => safeText(value).toLowerCase().includes(term));
    return matchesText && (category === "all" || event.category === category);
  });

  const sort = sortSelect.value;
  filteredEvents.sort((a, b) => {
    if (sort === "date-asc") return a.date.localeCompare(b.date);
    if (sort === "date-desc") return b.date.localeCompare(a.date);
    if (sort === "title-asc") return a.title.localeCompare(b.title);
    return b.title.localeCompare(a.title);
  });

  currentPage = 1;
  displayEvents();
}

function openRegistration(event) {
  form.reset();
  eventIdInput.value = event.id;
  selectedEvent.textContent = `Registering for: ${event.title}`;
  registrationMessage.textContent = "";
  registrationMessage.style.color = "";
  modal.classList.add("open");
  modal.setAttribute("aria-hidden", "false");
  document.body.style.overflow = "hidden";
  document.getElementById("name").focus();
}

function closeRegistration() {
  modal.classList.remove("open");
  modal.setAttribute("aria-hidden", "true");
  document.body.style.overflow = "";
}

searchInput.addEventListener("input", applyFilters);
categoryFilter.addEventListener("change", applyFilters);
sortSelect.addEventListener("change", applyFilters);
prevBtn.addEventListener("click", () => {
  if (currentPage > 1) { currentPage--; displayEvents(); }
});
nextBtn.addEventListener("click", () => {
  if (currentPage < Math.ceil(filteredEvents.length / eventsPerPage)) {
    currentPage++;
    displayEvents();
  }
});
document.getElementById("closeModal").addEventListener("click", closeRegistration);
modal.addEventListener("click", event => {
  if (event.target === modal) closeRegistration();
});
document.addEventListener("keydown", event => {
  if (event.key === "Escape" && modal.classList.contains("open")) closeRegistration();
});

form.addEventListener("submit", async event => {
  event.preventDefault();
  registrationMessage.textContent = "Submitting...";
  registrationMessage.style.color = "";

  const phone = document.getElementById("phone").value.trim();
  if (!/^[0-9]{10}$/.test(phone)) {
    registrationMessage.textContent = "Enter a valid 10-digit phone number.";
    registrationMessage.style.color = "crimson";
    return;
  }

  try {
    const response = await fetch("register_event.php", {
      method: "POST",
      body: new FormData(form)
    });
    const result = await response.json();
    registrationMessage.textContent = result.message;
    registrationMessage.style.color = result.success ? "green" : "crimson";
    if (result.success) {
      form.reset();
      window.setTimeout(closeRegistration, 1400);
    }
  } catch (error) {
    registrationMessage.textContent = "Registration could not be saved. Check your PHP/database setup.";
    registrationMessage.style.color = "crimson";
  }
});

document.getElementById("themeBtn").addEventListener("click", () => {
  document.body.classList.toggle("dark");
  const dark = document.body.classList.contains("dark");
  document.getElementById("themeBtn").textContent = dark ? "☀️ Light Mode" : "🌙 Dark Mode";
});

loadEvents();
