// =====================================================
// STUDENT HUB - PRACTICAL 6
// Fetch API, Search, Filter, Sorting and Pagination
// =====================================================

let events = [];
let filteredEvents = [];

let currentPage = 1;

const recordsPerPage = 5;

const searchInput =
    document.getElementById("searchInput");

const categoryFilter =
    document.getElementById("categoryFilter");

const sortSelect =
    document.getElementById("sortSelect");

const eventContainer =
    document.getElementById("eventContainer");

const loading =
    document.getElementById("loading");

const errorMessage =
    document.getElementById("error");

const pagination =
    document.getElementById("pagination");


// =====================================================
// FETCH JSON DATA
// =====================================================

async function loadEvents() {

    try {

        loading.style.display = "block";

        errorMessage.textContent = "";

        const response =
            await fetch("data/wrong.json");

        if (!response.ok) {
            throw new Error("Unable to load events.json");
        }

        events =
            await response.json();

        filteredEvents =
            [...events];

        createCategories();

        applyFilters();

        loading.style.display = "none";

    } catch (error) {

        loading.style.display = "none";

        errorMessage.textContent =
            "Error loading events. Please check the JSON file and file path.";

        console.error(error);
    }
}


// =====================================================
// CREATE CATEGORY OPTIONS
// =====================================================

function createCategories() {

    const categories =
        [...new Set(
            events.map(function (event) {
                return event.category;
            })
        )];

    categoryFilter.innerHTML =
        '<option value="all">All Categories</option>';

    categories.forEach(function (category) {

        const option =
            document.createElement("option");

        option.value =
            category;

        option.textContent =
            category;

        categoryFilter.appendChild(option);

    });
}


// =====================================================
// SEARCH + FILTER
// =====================================================

function applyFilters() {

    const searchText =
        searchInput.value.toLowerCase();

    const selectedCategory =
        categoryFilter.value;

    filteredEvents =
        events.filter(function (event) {

            const matchesSearch =
                event.title.toLowerCase().includes(searchText) ||
                event.description.toLowerCase().includes(searchText);

            const matchesCategory =
                selectedCategory === "all" ||
                event.category === selectedCategory;

            return matchesSearch && matchesCategory;

        });

    applySorting();

    currentPage = 1;

    displayEvents();

}


// =====================================================
// SORTING
// =====================================================

function applySorting() {

    const sortValue =
        sortSelect.value;

    if (sortValue === "newest") {

        filteredEvents.sort(function (a, b) {

            return new Date(b.date) -
                   new Date(a.date);

        });

    }

    else if (sortValue === "oldest") {

        filteredEvents.sort(function (a, b) {

            return new Date(a.date) -
                   new Date(b.date);

        });

    }

    else if (sortValue === "name") {

        filteredEvents.sort(function (a, b) {

            return a.title.localeCompare(b.title);

        });

    }
}


// =====================================================
// DISPLAY EVENTS
// =====================================================

function displayEvents() {

    eventContainer.innerHTML = "";

    const start =
        (currentPage - 1) * recordsPerPage;

    const end =
        start + recordsPerPage;

    const pageEvents =
        filteredEvents.slice(start, end);

    if (pageEvents.length === 0) {

        eventContainer.innerHTML =
            "<p>No events found.</p>";

        pagination.innerHTML = "";

        return;
    }

    pageEvents.forEach(function (event) {

        const card =
            document.createElement("div");

        card.className =
            "eventCard";

        card.innerHTML = `
            <h3>${event.title}</h3>
            <p><strong>Category:</strong> ${event.category}</p>
            <p><strong>Date:</strong> ${event.date}</p>
            <p>${event.description}</p>
        `;

        eventContainer.appendChild(card);

    });

    createPagination();

}


// =====================================================
// PAGINATION
// =====================================================

function createPagination() {

    pagination.innerHTML = "";

    const totalPages =
        Math.ceil(
            filteredEvents.length /
            recordsPerPage
        );

    if (totalPages <= 1) {
        return;
    }

    for (
        let page = 1;
        page <= totalPages;
        page++
    ) {

        const button =
            document.createElement("button");

        button.textContent =
            page;

        if (page === currentPage) {
            button.classList.add("active");
        }

        button.addEventListener(
            "click",
            function () {

                currentPage =
                    page;

                displayEvents();

            }
        );

        pagination.appendChild(button);

    }
}


// =====================================================
// EVENT LISTENERS
// =====================================================

searchInput.addEventListener(
    "input",
    applyFilters
);

categoryFilter.addEventListener(
    "change",
    applyFilters
);

sortSelect.addEventListener(
    "change",
    function () {

        applySorting();

        currentPage = 1;

        displayEvents();

    }
);


// =====================================================
// START
// =====================================================

loadEvents();