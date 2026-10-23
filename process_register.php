```php
<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: register.html");
    exit;
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$mobile = trim($_POST["mobile"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";
$course = trim($_POST["course"] ?? "");
$year = trim($_POST["year"] ?? "");
$gender = trim($_POST["gender"] ?? "");
$terms = isset($_POST["terms"]);

$errors = [];

if ($name === "" || strlen($name) < 3) {
    $errors[] = "Full name must contain at least 3 characters.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

if (!preg_match("/^[0-9]{10}$/", $mobile)) {
    $errors[] = "Mobile number must contain exactly 10 digits.";
}

if (
    strlen($password) < 8 ||
    !preg_match("/[A-Z]/", $password) ||
    !preg_match("/[a-z]/", $password) ||
    !preg_match("/[0-9]/", $password) ||
    !preg_match("/[^A-Za-z0-9]/", $password)
) {
    $errors[] = "Password must be at least 8 characters and include uppercase, lowercase, a number and a special character.";
}

if ($password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
}

$allowedCourses = ["CSE", "IT", "CE", "ECE", "ME"];
$allowedYears = ["1", "2", "3", "4"];
$allowedGenders = ["Male", "Female", "Other"];

if (!in_array($course, $allowedCourses, true)) {
    $errors[] = "Please select a valid course.";
}

if (!in_array($year, $allowedYears, true)) {
    $errors[] = "Please select a valid year.";
}

if (!in_array($gender, $allowedGenders, true)) {
    $errors[] = "Please select a valid gender.";
}

if (!$terms) {
    $errors[] = "You must agree to the Terms and Conditions.";
}

if (!empty($errors)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Registration Error</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <main class="container">
            <h1>Registration Failed</h1>
            <?php foreach ($errors as $error): ?>
                <p class="error">
                    <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endforeach; ?>
            <a href="register.html">Go Back to Registration</a>
        </main>
    </body>
    </html>
    <?php
    exit;
}

$check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email Already Registered</title>
    </head>
    <body>
        <h1>Registration Failed</h1>
        <p>This email address is already registered. Please use another email.</p>
        <a href="register.html">Go Back</a>
    </body>
    </html>
    <?php
    exit;
}

$check->close();

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$yearValue = (int)$year;
$termsAccepted = 1;
$role = "Student";

$stmt = $conn->prepare(
    "INSERT INTO users
    (name, email, mobile, password, course, year, gender, terms_accepted, role)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    error_log("Registration prepare failed: " . $conn->error);
    die("Registration could not be completed. Please try again.");
}

$stmt->bind_param(
    "sssssisis",
    $name,
    $email,
    $mobile,
    $passwordHash,
    $course,
    $yearValue,
    $gender,
    $termsAccepted,
    $role
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: login.php?registered=1");
    exit;
}

if ($stmt->errno === 1062) {
    $stmt->close();
    header("Location: register.html");
    exit;
}

error_log("Registration insert failed: " . $stmt->error);
$stmt->close();

die("Registration could not be completed. Please try again.");
?>
```