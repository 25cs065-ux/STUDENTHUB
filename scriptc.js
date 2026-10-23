// =====================================================
// STUDENT HUB - PRACTICAL 4
// Common JavaScript File: scriptc.js
// =====================================================

// =====================================================
// 1. HAMBURGER / MENU BUTTON
// =====================================================

const menuButton = document.getElementById("menuButton");
const navMenu = document.getElementById("navMenu");

if (menuButton && navMenu) {

    menuButton.addEventListener("click", function () {

        const isOpen = !navMenu.hasAttribute("hidden");

        if (isOpen) {

            navMenu.setAttribute("hidden", "");

            menuButton.setAttribute(
                "aria-expanded",
                "false"
            );

        } else {

            navMenu.removeAttribute("hidden");

            menuButton.setAttribute(
                "aria-expanded",
                "true"
            );
        }

    });
}


// =====================================================
// 2. LIGHT / DARK MODE
// =====================================================

const themeButton = document.getElementById("themeButton");

const savedTheme =
    localStorage.getItem("studentHubTheme");

if (savedTheme === "dark") {

    document.body.classList.add("darkMode");

    if (themeButton) {
        themeButton.textContent = "☀️ Light Mode";
    }
}

if (themeButton) {

    themeButton.addEventListener("click", function () {

        document.body.classList.toggle("darkMode");

        const darkMode =
            document.body.classList.contains("darkMode");

        if (darkMode) {

            localStorage.setItem(
                "studentHubTheme",
                "dark"
            );

            themeButton.textContent =
                "☀️ Light Mode";

        } else {

            localStorage.setItem(
                "studentHubTheme",
                "light"
            );

            themeButton.textContent =
                "🌙 Dark Mode";
        }

    });
}


// =====================================================
// 3. NOTIFICATION BANNER
// =====================================================

const notification =
    document.getElementById("notification");

const closeNotification =
    document.getElementById("closeNotification");

if (notification && closeNotification) {

    closeNotification.addEventListener(
        "click",
        function (event) {

            event.preventDefault();

            event.stopPropagation();

            notification.style.display = "none";

        }
    );
}


// =====================================================
// 4. COLLAPSIBLE FAQ
// =====================================================

const faqQuestions =
    document.querySelectorAll(".faqQuestion");

faqQuestions.forEach(function (question) {

    question.addEventListener(
        "click",
        function () {

            const answer =
                question.nextElementSibling;

            if (!answer) {
                return;
            }

            const isAlreadyOpen =
                answer.classList.contains("open");

            document
                .querySelectorAll(".faqAnswer")
                .forEach(function (item) {

                    item.classList.remove("open");

                    item.style.display = "none";

                });

            faqQuestions.forEach(function (item) {

                item.setAttribute(
                    "aria-expanded",
                    "false"
                );

            });

            if (!isAlreadyOpen) {

                answer.classList.add("open");

                answer.style.display = "block";

                question.setAttribute(
                    "aria-expanded",
                    "true"
                );
            }

        }
    );

});


// =====================================================
// 5. MODAL POPUP
// =====================================================

const openModal =
    document.getElementById("openModal");

const closeModal =
    document.getElementById("closeModal");

const modal =
    document.getElementById("modal");

if (openModal && modal) {

    openModal.addEventListener(
        "click",
        function () {

            modal.hidden = false;

            if (closeModal) {
                closeModal.focus();
            }

        }
    );
}

if (closeModal && modal) {

    closeModal.addEventListener(
        "click",
        function () {

            modal.hidden = true;

            if (openModal) {
                openModal.focus();
            }

        }
    );
}

if (modal) {

    modal.addEventListener(
        "click",
        function (event) {

            if (event.target === modal) {

                modal.hidden = true;

                if (openModal) {
                    openModal.focus();
                }

            }

        }
    );
}

document.addEventListener(
    "keydown",
    function (event) {

        if (
            event.key === "Escape" &&
            modal &&
            !modal.hidden
        ) {

            modal.hidden = true;

            if (openModal) {
                openModal.focus();
            }

        }

    }
);


// =====================================================
// 6. CONTENT SLIDER
// =====================================================

const sliderImage =
    document.getElementById("sliderImage");

const sliderTitle =
    document.getElementById("sliderTitle");

const sliderDescription =
    document.getElementById("sliderDescription");

const previousSlide =
    document.getElementById("previousSlide");

const nextSlide =
    document.getElementById("nextSlide");

const slides = [
    {
        image: "slider1.png",
        title: "Welcome to Student Hub",
        description: "Your one place for college information."
    },
    {
        image: "slider2.png",
        title: "Learn and Grow",
        description: "Access study materials and useful resources."
    },
    {
        image: "slider3.png",
        title: "Stay Updated",
        description: "Check the latest college events and notices."
    }
];

let currentSlide = 0;

function showSlide(index) {

    if (!sliderImage) {
        return;
    }

    const slide =
        slides[index];

    sliderImage.src =
        slide.image;

    sliderImage.alt =
        slide.title;

    if (sliderTitle) {
        sliderTitle.textContent =
            slide.title;
    }

    if (sliderDescription) {
        sliderDescription.textContent =
            slide.description;
    }
}

if (nextSlide) {

    nextSlide.addEventListener(
        "click",
        function () {

            currentSlide++;

            if (currentSlide >= slides.length) {
                currentSlide = 0;
            }

            showSlide(currentSlide);

        }
    );
}

if (previousSlide) {

    previousSlide.addEventListener(
        "click",
        function () {

            currentSlide--;

            if (currentSlide < 0) {
                currentSlide =
                    slides.length - 1;
            }

            showSlide(currentSlide);

        }
    );
}


// =====================================================
// 7. CONSOLE MESSAGE
// =====================================================

console.log(
    "StudentHub Practical 4 scriptc.js loaded successfully."
);