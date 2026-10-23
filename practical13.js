
const API = "api.php";
let csrfToken = "";

const form = document.getElementById("eventForm");
const messageBox = document.getElementById("message");
const eventsBox = document.getElementById("events");
const loading = document.getElementById("loading");
const saveButton = document.getElementById("saveButton");
const cancelButton = document.getElementById("cancelButton");
const refreshButton = document.getElementById("refreshButton");

function showMessage(message, type = "success") {
    messageBox.textContent = message;
    messageBox.className = type;
}

function clearMessage() {
    messageBox.textContent = "";
    messageBox.className = "";
}

async function request(method = "GET", data = null) {
    const options = {
        method,
        headers: { "Accept": "application/json" }
    };

    if (data !== null) {
        options.headers["Content-Type"] = "application/json";
        options.headers["X-CSRF-Token"] = csrfToken;
        options.body = JSON.stringify(data);
    }

    const response = await fetch(API, options);
    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || `Request failed: ${response.status}`);
    }

    return result;
}

function escapeHTML(value) {
    return String(value ?? "").replace(/[&<>"']/g, character => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
    })[character]);
}

function formatDate(value) {
    const date = new Date(String(value).replace(" ", "T"));
    return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
}

function resetForm() {
    form.reset();
    document.getElementById("event_id").value = "";
    document.getElementById("formTitle").textContent = "Add New Event";
    saveButton.textContent = "Add Event";
    cancelButton.hidden = true;
}

function renderEvents(events) {
    if (!events.length) {
        eventsBox.innerHTML = '<div class="empty">No events found. Add your first event above.</div>';
        return;
    }

    eventsBox.innerHTML = events.map(event => `
        <article class="event">
            <h3>${escapeHTML(event.event_name)}</h3>
            <p><strong>Date:</strong> ${escapeHTML(formatDate(event.event_date))}</p>
            <p><strong>Venue:</strong> ${escapeHTML(event.venue)}</p>
            <p><span class="status">${escapeHTML(event.status)}</span></p>
            <p>${escapeHTML(event.description || "No description")}</p>
            <div class="actions">
                <button class="secondary" type="button" data-edit="${Number(event.event_id)}">Edit</button>
                <button class="danger" type="button" data-delete="${Number(event.event_id)}">Delete</button>
            </div>
        </article>
    `).join("");
}

async function loadEvents(showLoading = true) {
    if (showLoading) loading.hidden = false;
    loading.textContent = "Loading events...";
    eventsBox.innerHTML = "";
    clearMessage();

    try {
        const result = await request("GET");
        csrfToken = result.csrf_token;
        renderEvents(result.data || []);
        loading.hidden = true;
    } catch (error) {
        loading.hidden = false;
        loading.textContent = "Unable to load events.";
        showMessage(error.message + " Check your login, database connection and API response.", "error");
    }
}

form.addEventListener("submit", async event => {
    event.preventDefault();
    clearMessage();

    const id = document.getElementById("event_id").value;
    const eventDate = document.getElementById("event_date").value;

    const data = {
        event_name: document.getElementById("event_name").value.trim(),
        event_date: eventDate,
        venue: document.getElementById("venue").value.trim(),
        status: document.getElementById("status").value,
        description: document.getElementById("description").value.trim()
    };

    if (!data.event_name || !data.event_date || !data.venue || !data.description) {
        showMessage("Please complete all required fields.", "error");
        return;
    }

    saveButton.disabled = true;
    saveButton.textContent = id ? "Updating..." : "Adding...";

    try {
        const result = await request(id ? "PUT" : "POST", id ? { ...data, event_id: Number(id) } : data);
        resetForm();
        await loadEvents(false);
        showMessage(result.message);
    } catch (error) {
        showMessage(error.message, "error");
    } finally {
        saveButton.disabled = false;
        saveButton.textContent = document.getElementById("event_id").value ? "Update Event" : "Add Event";
    }
});

eventsBox.addEventListener("click", async event => {
    const editButton = event.target.closest("[data-edit]");
    const deleteButton = event.target.closest("[data-delete]");

    if (editButton) {
        const id = Number(editButton.dataset.edit);

        try {
            const result = await request("GET");
            csrfToken = result.csrf_token;
            const item = result.data.find(event => Number(event.event_id) === id);

            if (!item) throw new Error("Event not found.");

            document.getElementById("event_id").value = item.event_id;
            document.getElementById("event_name").value = item.event_name;
            document.getElementById("event_date").value = item.event_date.replace(" ", "T").slice(0, 16);
            document.getElementById("venue").value = item.venue;
            document.getElementById("status").value = item.status;
            document.getElementById("description").value = item.description || "";
            document.getElementById("formTitle").textContent = "Edit Event";
            saveButton.textContent = "Update Event";
            cancelButton.hidden = false;
            clearMessage();
            form.scrollIntoView({ behavior: "smooth", block: "start" });
        } catch (error) {
            showMessage(error.message, "error");
        }
    }

    if (deleteButton) {
        const id = Number(deleteButton.dataset.delete);

        if (!confirm("Are you sure you want to delete this event?")) return;

        deleteButton.disabled = true;

        try {
            const result = await request("DELETE", { event_id: id });
            await loadEvents(false);
            showMessage(result.message);
        } catch (error) {
            showMessage(error.message, "error");
        }
    }
});

cancelButton.addEventListener("click", () => {
    resetForm();
    clearMessage();
});

refreshButton.addEventListener("click", () => loadEvents());

loadEvents();
