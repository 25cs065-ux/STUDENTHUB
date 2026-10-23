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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: manage_students.php");
    exit;
}

if (!hash_equals($_SESSION["csrf_token"] ?? "", $_POST["csrf_token"] ?? "")) {
    header("Location: manage_students.php?error=csrf");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: manage_students.php?error=invalid");
    exit;
}

$stmt = $conn->prepare(
    "UPDATE users SET status = 'inactive' WHERE id = ? AND role = 'Student'"
);

if (!$stmt) {
    error_log($conn->error);
    header("Location: manage_students.php?error=failed");
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$changed = $stmt->affected_rows;
$stmt->close();

if ($changed > 0) {
    writeAuditLog($conn, "STUDENT_DEACTIVATED", "Deactivated student ID: " . $id);
}

$conn->close();

if ($changed > 0) {
    header("Location: manage_students.php?message=deleted");
} else {
    header("Location: manage_students.php?error=notfound");
}
exit;
?>