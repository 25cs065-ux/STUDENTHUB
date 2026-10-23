```php
<?php
session_start();
require_once "db.php";
require_once "audit.php";

if (!isset($_SESSION["user_id"], $_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php?error=unauthorized");
    exit;
}

if (!isset($_SESSION["last_activity"]) || time() - $_SESSION["last_activity"] > 900) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=timeout");
    exit;
}

$_SESSION["last_activity"] = time();

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        header("Location: manage_students.php?error=csrf");
        exit;
    }

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $course = trim($_POST["course"] ?? "");
    $year = (int)($_POST["year"] ?? 0);
    $gender = trim($_POST["gender"] ?? "");

    $allowedCourses = ["CSE", "IT", "CE", "ECE", "ME"];
    $allowedGenders = ["Male", "Female", "Other"];

    if (
        !$id || strlen($name) < 3 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !preg_match("/^[0-9]{10}$/", $mobile) ||
        !in_array($course, $allowedCourses, true) ||
        $year < 1 || $year > 4 ||
        !in_array($gender, $allowedGenders, true)
    ) {
        header("Location: edit_student.php?id=" . (int)$id . "&error=invalid");
        exit;
    }

    $check = $conn->prepare(
        "SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1"
    );
    $check->bind_param("si", $email, $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        header("Location: edit_student.php?id=" . $id . "&error=duplicate");
        exit;
    }
    $check->close();

    $stmt = $conn->prepare(
        "UPDATE users
         SET name = ?, email = ?, mobile = ?, course = ?, year = ?, gender = ?
         WHERE id = ? AND role = 'Student'"
    );
    $stmt->bind_param("ssssisi", $name, $email, $mobile, $course, $year, $gender, $id);

    if ($stmt->execute()) {
        $stmt->close();
        writeAuditLog($conn, "STUDENT_UPDATED", "Updated student ID: " . $id);
        header("Location: manage_students.php?message=updated");
        exit;
    }

    $stmt->close();
    header("Location: edit_student.php?id=" . $id . "&error=failed");
    exit;
}

if (!$id) {
    header("Location: manage_students.php?error=notfound");
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, name, email, mobile, course, year, gender, status
     FROM users WHERE id = ? AND role = 'Student' LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: manage_students.php?error=notfound");
    exit;
}

$message = "";
$error = $_GET["error"] ?? "";

if ($error === "invalid") {
    $message = "Please check all fields and enter valid details.";
} elseif ($error === "duplicate") {
    $message = "Another account already uses this email address.";
} elseif ($error === "failed") {
    $message = "Update failed. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student | STUDENTHUB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container student-management">
    <h1>Edit Student Details</h1>

    <?php if ($message !== ""): ?>
        <div class="notice-message error"><?= e($message) ?></div>
    <?php endif; ?>

    <section class="management-card">
        <form method="post" action="edit_student.php" class="student-form">
            <input type="hidden" name="id" value="<?= (int)$student["id"] ?>">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">

            <label for="name">Full Name</label>
            <input id="name" name="name" type="text" minlength="3" required value="<?= e($student["name"]) ?>">

            <label for="email">Email</label>
            <input id="email" name="email" type="email" required value="<?= e($student["email"]) ?>">

            <label for="mobile">Mobile Number</label>
            <input id="mobile" name="mobile" type="text" pattern="[0-9]{10}" maxlength="10" required value="<?= e($student["mobile"]) ?>">

            <label for="course">Course</label>
            <select id="course" name="course" required>
                <?php foreach (["CSE", "IT", "CE", "ECE", "ME"] as $course): ?>
                    <option value="<?= e($course) ?>" <?= $student["course"] === $course ? "selected" : "" ?>>
                        <?= e($course) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="year">Year</label>
            <select id="year" name="year" required>
                <?php for ($y = 1; $y <= 4; $y++): ?>
                    <option value="<?= $y ?>" <?= (int)$student["year"] === $y ? "selected" : "" ?>>
                        Year <?= $y ?>
                    </option>
                <?php endfor; ?>
            </select>

            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <?php foreach (["Male", "Female", "Other"] as $gender): ?>
                    <option value="<?= e($gender) ?>" <?= $student["gender"] === $gender ? "selected" : "" ?>>
                        <?= e($gender) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <p><strong>Current status:</strong> <?= e(ucfirst($student["status"])) ?></p>

            <button type="submit">Save Changes</button>
            <a href="manage_students.php">Cancel</a>
        </form>
    </section>
</main>
</body>
</html>
<?php $conn->close(); ?>
```