const eventsContainer = document.getElementById("eventsContainer");
const searchInput = document.getElementById("searchInput");
const categoryFilter = document.getElementById("categoryFilter");
const sortSelect = document.getElementById("sortSelect");
const resultCount = document.getElementById("resultCount");
const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");
const pageInfo = document.getElementById("pageInfo");

let events = [];
let filteredEvents = [];
let currentPage = 1;

const eventsPerPage = 4;

async function loadEvents() {
    try {
        const response = await fetch("events.json");

        if (!response.ok) {
            throw new Error("Unable to load events.json");
        }

        events = await response.json();
        filteredEvents = [...events];

        createCategoryOptions();
        displayEvents();

    } catch (error) {
        console.error(error);

        eventsContainer.innerHTML = `
            <div class="no-results">
                <h3>Unable to load events</h3>
                <p>Please make sure events.json is available.</p>
            </div>
        `;
    }
}

function createCategoryOptions() {
    const categories = [...new Set(
        events.map(event => event.category)
    )];

    categories.sort();

    categories.forEach(category => {
        const option = document.createElement("option");

        option.value = category;
        option.textContent = category;

        categoryFilter.appendChild(option);
    });
}

function formatDate(dateString) {
    const date = new Date(dateString);

    return date.toLocaleDateString("en-IN", {
        day: "2-digit",
        month: "long",
        year: "numeric"
    });
}

function displayEvents() {
    eventsContainer.innerHTML = "";

    const totalPages = Math.ceil(
        filteredEvents.length / eventsPerPage
    );

    if (currentPage > totalPages && totalPages > 0) {
        currentPage = totalPages;
    }

    const startIndex =
        (currentPage - 1) * eventsPerPage;

    const endIndex =
        startIndex + eventsPerPage;

    const eventsToDisplay =
        filteredEvents.slice(startIndex, endIndex);

    if (eventsToDisplay.length === 0) {
        eventsContainer.innerHTML = `
            <div class="no-results">
                <h3>No events found</h3>
                <p>Try changing your search or filter.</p>
            </div>
        `;
    } else {
        eventsToDisplay.forEach(event => {
            const card = document.createElement("article");

            card.className = "card";

            card.innerHTML = `
                <img
                    class="card-img"
                    src="${event.image}"
                    alt="${event.title}"
                >

                <div class="card-body">

                    <span class="card-category">
                        ${event.category}
                    </span>

                    <h3 class="card-title">
                        ${event.title}
                    </h3>

                    <div class="card-meta">
                        ${formatDate(event.date)}
                    </div>

                    <p class="card-text">
                        ${event.description}
                    </p>

                    <a
                        href="${event.registerLink}"
                        class="btn-primary"
                    >
                        Register Now
                    </a>

                </div>
            `;

            eventsContainer.appendChild(card);
        });
    }

    resultCount.textContent =
        `${filteredEvents.length} event(s) found`;

    if (totalPages === 0) {
        pageInfo.textContent = "Page 0 of 0";
    } else {
        pageInfo.textContent =
            `Page ${currentPage} of ${totalPages}`;
    }

    prevBtn.disabled = currentPage === 1;

    nextBtn.disabled =
        currentPage >= totalPages || totalPages === 0;
}

function applyFilters() {
    const searchTerm =
        searchInput.value.toLowerCase().trim();

    const selectedCategory =
        categoryFilter.value;

    const sortValue =
        sortSelect.value;

    filteredEvents = events.filter(event => {
        const matchesSearch =
            event.title.toLowerCase().includes(searchTerm) ||
            event.description.toLowerCase().includes(searchTerm) ||
            event.category.toLowerCase().includes(searchTerm);

        const matchesCategory =
            selectedCategory === "all" ||
            event.category === selectedCategory;

        return matchesSearch && matchesCategory;
    });

    filteredEvents.sort((a, b) => {
        if (sortValue === "date-asc") {
            return new Date(a.date) - new Date(b.date);
        }

        if (sortValue === "date-desc") {
            return new Date(b.date) - new Date(a.date);
        }

        if (sortValue === "title-asc") {
            return a.title.localeCompare(b.title);
        }

        if (sortValue === "title-desc") {
            return b.title.localeCompare(a.title);
        }
    });

    currentPage = 1;

    displayEvents();
}

searchInput.addEventListener("input", applyFilters);

categoryFilter.addEventListener("change", applyFilters);

sortSelect.addEventListener("change", applyFilters);

prevBtn.addEventListener("click", () => {
    if (currentPage > 1) {
        currentPage--;

        displayEvents();

        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    }
});

nextBtn.addEventListener("click", () => {
    const totalPages =
        Math.ceil(
            filteredEvents.length / eventsPerPage
        );

    if (currentPage < totalPages) {
        currentPage++;

        displayEvents();

        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    }
});

loadEvents();
