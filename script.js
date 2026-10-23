const form = document.getElementById("registrationForm");

const nameInput = document.getElementById("name");
const emailInput = document.getElementById("email");
const mobileInput = document.getElementById("mobile");
const passwordInput = document.getElementById("password");
const confirmPasswordInput =
    document.getElementById("confirmPassword");

const courseInput = document.getElementById("course");
const yearInput = document.getElementById("year");
const termsInput = document.getElementById("terms");

const strengthBar = document.getElementById("strengthBar");
const strengthText = document.getElementById("strengthText");
const successMessage = document.getElementById("successMessage");


// =====================================
// REGULAR EXPRESSIONS
// =====================================

const nameRegex = /^[A-Za-z ]{3,50}$/;

const emailRegex =
    /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const mobileRegex =
    /^[6-9][0-9]{9}$/;

const upperCaseRegex = /[A-Z]/;

const lowerCaseRegex = /[a-z]/;

const numberRegex = /[0-9]/;

const specialCharRegex =
    /[!@#$%^&*(),.?":{}|<>_\-+=/\\[\];']/;


// =====================================
// SHOW ERROR
// =====================================

function showError(id, message) {

    document.getElementById(id).textContent = message;
}


// =====================================
// CLEAR ALL OLD ERRORS
// =====================================

function clearErrors() {

    const errors = document.querySelectorAll(".error");

    errors.forEach(function(error) {
        error.textContent = "";
    });

    // Also remove previous success message
    successMessage.textContent = "";
}


// =====================================
// PASSWORD STRENGTH
// =====================================

function checkPasswordStrength(password) {

    let score = 0;

    if (password.length >= 8) {
        score++;
    }

    if (upperCaseRegex.test(password)) {
        score++;
    }

    if (lowerCaseRegex.test(password)) {
        score++;
    }

    if (numberRegex.test(password)) {
        score++;
    }

    if (specialCharRegex.test(password)) {
        score++;
    }


    if (password.length === 0) {

        strengthBar.style.width = "0%";

        strengthText.textContent =
            "Password strength: Not entered";

        return;
    }


    if (score <= 2) {

        strengthBar.style.width = "30%";

        strengthText.textContent =
            "Password strength: Weak";

    }
    else if (score <= 4) {

        strengthBar.style.width = "65%";

        strengthText.textContent =
            "Password strength: Medium";

    }
    else {

        strengthBar.style.width = "100%";

        strengthText.textContent =
            "Password strength: Strong";
    }
}


// =====================================
// CHECK PASSWORD WHILE TYPING
// =====================================

passwordInput.addEventListener(
    "input",
    function() {

        checkPasswordStrength(
            passwordInput.value
        );

        // Remove old password error
        document.getElementById(
            "passwordError"
        ).textContent = "";

        // Remove old confirm password error
        document.getElementById(
            "confirmPasswordError"
        ).textContent = "";

        // Remove success message because
        // user has changed the form again
        successMessage.textContent = "";
    }
);


// =====================================
// CONFIRM PASSWORD WHILE TYPING
// =====================================

confirmPasswordInput.addEventListener(
    "input",
    function() {

        document.getElementById(
            "confirmPasswordError"
        ).textContent = "";

        successMessage.textContent = "";
    }
);


// =====================================
// FORM SUBMISSION
// =====================================

form.addEventListener(
    "submit",
    function(event) {

        // Prevent normal form submission
        event.preventDefault();


        // VERY IMPORTANT:
        // Remove all previous error messages
        clearErrors();


        let isValid = true;


        // =================================
        // NAME
        // =================================

        const name = nameInput.value.trim();

        if (name === "") {

            showError(
                "nameError",
                "Name is required."
            );

            isValid = false;

        }
        else if (!nameRegex.test(name)) {

            showError(
                "nameError",
                "Name should contain only letters and spaces."
            );

            isValid = false;
        }


        // =================================
        // EMAIL
        // =================================

        const email = emailInput.value.trim();

        if (email === "") {

            showError(
                "emailError",
                "Email is required."
            );

            isValid = false;

        }
        else if (!emailRegex.test(email)) {

            showError(
                "emailError",
                "Please enter a valid email address."
            );

            isValid = false;
        }


        // =================================
        // MOBILE
        // =================================

        const mobile = mobileInput.value.trim();

        if (mobile === "") {

            showError(
                "mobileError",
                "Mobile number is required."
            );

            isValid = false;

        }
        else if (!mobileRegex.test(mobile)) {

            showError(
                "mobileError",
                "Enter a valid 10-digit Indian mobile number."
            );

            isValid = false;
        }


        // =================================
        // PASSWORD
        // =================================

        const password = passwordInput.value;

        if (password === "") {

            showError(
                "passwordError",
                "Password is required."
            );

            isValid = false;

        }
        else if (password.length < 8) {

            showError(
                "passwordError",
                "Password must contain at least 8 characters."
            );

            isValid = false;

        }
        else if (!upperCaseRegex.test(password)) {

            showError(
                "passwordError",
                "Password must contain an uppercase letter."
            );

            isValid = false;

        }
        else if (!lowerCaseRegex.test(password)) {

            showError(
                "passwordError",
                "Password must contain a lowercase letter."
            );

            isValid = false;

        }
        else if (!numberRegex.test(password)) {

            showError(
                "passwordError",
                "Password must contain a number."
            );

            isValid = false;

        }
        else if (!specialCharRegex.test(password)) {

            showError(
                "passwordError",
                "Password must contain a special character."
            );

            isValid = false;
        }


        // =================================
        // CONFIRM PASSWORD
        // =================================

        const confirmPassword =
            confirmPasswordInput.value;

        if (confirmPassword === "") {

            showError(
                "confirmPasswordError",
                "Please confirm your password."
            );

            isValid = false;

        }
        else if (password !== confirmPassword) {

            showError(
                "confirmPasswordError",
                "Passwords do not match."
            );

            isValid = false;
        }


        // =================================
        // COURSE
        // =================================

        if (courseInput.value === "") {

            showError(
                "courseError",
                "Please select a course."
            );

            isValid = false;
        }


        // =================================
        // YEAR
        // =================================

        if (yearInput.value === "") {

            showError(
                "yearError",
                "Please select your year."
            );

            isValid = false;
        }


        // =================================
        // GENDER
        // =================================

        const gender =
            document.querySelector(
                'input[name="gender"]:checked'
            );

        if (!gender) {

            showError(
                "genderError",
                "Please select your gender."
            );

            isValid = false;
        }


        // =================================
        // TERMS
        // =================================

        if (!termsInput.checked) {

            showError(
                "termsError",
                "You must accept the Terms and Conditions."
            );

            isValid = false;
        }


        // =================================
        // FINAL RESULT
        // =================================

                if (isValid) {
            successMessage.textContent = "Validation passed. Submitting...";
            form.submit();
        }

    }
);