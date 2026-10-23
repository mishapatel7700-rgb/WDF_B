const API_URL = 'api.php';
let localEvents = [];

document.addEventListener('DOMContentLoaded', () => {
    fetchEvents();
    document.getElementById('eventForm').addEventListener('submit', handleAddEvent);
});

async function fetchEvents() {
    try {
        const response = await fetch(API_URL);
        if (response.ok) {
            localEvents = await response.json();
            renderEvents();
        }
    } catch (err) {
        console.error('Error fetching data:', err);
    }
}

function renderEvents() {
    const container = document.getElementById('eventsContainer');
    container.innerHTML = '';

    if (localEvents.length === 0) {
        container.innerHTML = '<p style="color: #7f8c8d; font-style: italic;">No events found.</p>';
        return;
    }

    localEvents.forEach(event => {
        const item = document.createElement('div');
        item.className = 'event-item';
        
        // Show delete button dynamically based on layout needs
        const deleteButtonHtml = `<button class="delete-btn" onclick="handleDeleteEvent(${event.id})">Delete</button>`;

        item.innerHTML = `
            <div class="event-category">${event.category || 'Event'} (${event.date})</div>
            <div class="event-title">${event.title} (${event.date})</div>
            <div class="event-desc">${event.description}</div>
            ${deleteButtonHtml}
        `;
        container.appendChild(item);
    });
}

// POST Method (Asynchronous creation)
async function handleAddEvent(e) {
    e.preventDefault();

    const titleInput = document.getElementById('eventTitle').value;
    const dateInput = document.getElementById('executionDate').value;
    const descInput = document.getElementById('detailedDescription').value;

    const newEventData = {
        title: titleInput,
        date: dateInput,
        description: descInput
    };

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            body: JSON.stringify(newEventData),
            headers: { 'Content-type': 'application/json' },
        });

        if (response.ok) {
            fetchEvents(); // Reload completely from the updated database state
            document.getElementById('eventForm').reset();
        }
    } catch (err) {
        console.error('Error posting data:', err);
    }
}

// DELETE Method (Asynchronous item deletion)
async function handleDeleteEvent(id) {
    try {
        const response = await fetch(`${API_URL}?id=${id}`, { method: 'DELETE' });
        if (response.ok) {
            fetchEvents(); // Reload updated entries directly from DB
        }
    } catch (err) {
        console.error('Error removing data:', err);
    }
}