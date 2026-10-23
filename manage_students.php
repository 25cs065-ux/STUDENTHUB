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

$message = "";
$messageType = "success";

if (isset($_GET["message"])) {
    $messages = [
        "added" => "Student added successfully.",
        "updated" => "Student details updated successfully.",
        "deleted" => "Student status changed to inactive successfully."
    ];

    if (isset($messages[$_GET["message"]])) {
        $message = $messages[$_GET["message"]];
    }
}

if (isset($_GET["error"])) {
    $errors = [
        "invalid" => "Please check the submitted details.",
        "duplicate" => "This email address is already registered.",
        "notfound" => "Student record was not found.",
        "failed" => "The operation failed. Please try again.",
        "csrf" => "Invalid request. Please refresh the page."
    ];

    $message = $errors[$_GET["error"]] ?? "Something went wrong.";
    $messageType = "error";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_student"])) {
    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        header("Location: manage_students.php?error=csrf");
        exit;
    }

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $mobile = trim($_POST["mobile"] ?? "");
    $password = $_POST["password"] ?? "";
    $course = trim($_POST["course"] ?? "");
    $year = (int)($_POST["year"] ?? 0);
    $gender = trim($_POST["gender"] ?? "");

    $allowedCourses = ["CSE", "IT", "CE", "ECE", "ME"];
    $allowedGenders = ["Male", "Female", "Other"];

    if (
        strlen($name) < 3 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !preg_match("/^[0-9]{10}$/", $mobile) ||
        strlen($password) < 8 ||
        !in_array($course, $allowedCourses, true) ||
        $year < 1 || $year > 4 ||
        !in_array($gender, $allowedGenders, true)
    ) {
        header("Location: manage_students.php?error=invalid");
        exit;
    }

    $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        header("Location: manage_students.php?error=duplicate");
        exit;
    }
    $check->close();

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $termsAccepted = 1;
    $role = "Student";

    $stmt = $conn->prepare(
        "INSERT INTO users
        (name, email, mobile, password, course, year, gender, terms_accepted, role)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        error_log($conn->error);
        header("Location: manage_students.php?error=failed");
        exit;
    }

    $stmt->bind_param(
        "sssssisis",
        $name,
        $email,
        $mobile,
        $passwordHash,
        $course,
        $year,
        $gender,
        $termsAccepted,
        $role
    );

    if ($stmt->execute()) {
        $stmt->close();
        writeAuditLog($conn, "STUDENT_ADDED", "Added student: " . $email);
        header("Location: manage_students.php?message=added");
        exit;
    }

    $errorCode = $stmt->errno;
    $stmt->close();

    header("Location: manage_students.php?error=" . ($errorCode === 1062 ? "duplicate" : "failed"));
    exit;
}

$search = trim($_GET["search"] ?? "");
$courseFilter = trim($_GET["course"] ?? "");
$statusFilter = trim($_GET["status"] ?? "active");

$allowedCourses = ["CSE", "IT", "CE", "ECE", "ME"];

if ($courseFilter !== "" && !in_array($courseFilter, $allowedCourses, true)) {
    $courseFilter = "";
}

if (!in_array($statusFilter, ["active", "inactive", "all"], true)) {
    $statusFilter = "active";
}

$sql = "SELECT id, name, email, mobile, course, year, gender, status
        FROM users WHERE role = 'Student'";
$params = [];
$types = "";

if ($search !== "") {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR CAST(id AS CHAR) LIKE ?)";
    $term = "%" . $search . "%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= "sss";
}

if ($courseFilter !== "") {
    $sql .= " AND course = ?";
    $params[] = $courseFilter;
    $types .= "s";
}

if ($statusFilter !== "all") {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Could not load student records. Check your database structure.");
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management | STUDENTHUB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container student-management">
    <h1>Student Management</h1>
    <p>Manage student records from the STUDENTHUB database.</p>

    <p>
        <a href="admin_dashboard.php">Admin Dashboard</a> |
        <a href="logout.php">Logout</a>
    </p>

    <?php if ($message !== ""): ?>
        <div class="notice-message <?= e($messageType) ?>" role="status">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <section class="management-card">
        <h2>Add New Student</h2>

        <form method="post" action="manage_students.php" class="student-form">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">

            <label for="name">Full Name</label>
            <input id="name" name="name" type="text" minlength="3" required>

            <label for="email">Email</label>
            <input id="email" name="email" type="email" required>

            <label for="mobile">Mobile Number</label>
            <input id="mobile" name="mobile" type="text" pattern="[0-9]{10}" maxlength="10" required>

            <label for="password">Initial Password</label>
            <input id="password" name="password" type="password" minlength="8" required>
            <small>Set a password for the student's account and communicate it securely.</small>

            <label for="course">Course</label>
            <select id="course" name="course" required>
                <option value="">Select course</option>
                <option value="CSE">CSE</option>
                <option value="IT">IT</option>
                <option value="CE">CE</option>
                <option value="ECE">ECE</option>
                <option value="ME">ME</option>
            </select>

            <label for="year">Year</label>
            <select id="year" name="year" required>
                <option value="">Select year</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
            </select>

            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="">Select gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>

            <button type="submit" name="add_student">Add Student</button>
        </form>
    </section>

    <section class="management-card">
        <h2>Student Records</h2>

        <form method="get" action="manage_students.php" class="filter-form">
            <label for="search">Search by name, email or ID</label>
            <input id="search" name="search" type="search" value="<?= e($search) ?>" placeholder="Enter search term">

            <label for="course_filter">Filter by course</label>
            <select id="course_filter" name="course">
                <option value="">All Courses</option>
                <?php foreach ($allowedCourses as $c): ?>
                    <option value="<?= e($c) ?>" <?= $courseFilter === $c ? "selected" : "" ?>>
                        <?= e($c) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="status_filter">Student status</label>
            <select id="status_filter" name="status">
                <option value="active" <?= $statusFilter === "active" ? "selected" : "" ?>>Active</option>
                <option value="inactive" <?= $statusFilter === "inactive" ? "selected" : "" ?>>Inactive</option>
                <option value="all" <?= $statusFilter === "all" ? "selected" : "" ?>>All statuses</option>
            </select>

            <button type="submit">Apply Filters</button>
            <a href="manage_students.php">Clear Filters</a>
        </form>

        <p>
            <a class="action-link" href="export_students.php">Export CSV</a>
        </p>

        <div class="table-wrapper">
            <table class="student-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Course</th>
                        <th>Year</th>
                        <th>Gender</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($student = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int)$student["id"] ?></td>
                            <td><?= e($student["name"]) ?></td>
                            <td><?= e($student["email"]) ?></td>
                            <td><?= e($student["mobile"]) ?></td>
                            <td><?= e($student["course"]) ?></td>
                            <td><?= (int)$student["year"] ?></td>
                            <td><?= e($student["gender"]) ?></td>
                            <td>
                                <span class="status-label <?= e($student["status"]) ?>">
                                    <?= e(ucfirst($student["status"])) ?>
                                </span>
                            </td>
                            <td class="action-cell">
                                <a href="edit_student.php?id=<?= (int)$student["id"] ?>">Edit</a>

                                <?php if ($student["status"] === "active"): ?>
                                    <form method="post" action="delete_student.php"
                                          onsubmit="return confirm('Are you sure you want to mark this student as inactive?');">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION["csrf_token"]) ?>">
                                        <input type="hidden" name="id" value="<?= (int)$student["id"] ?>">
                                        <button type="submit" class="danger-button">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">No student records found for the selected filters.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
```