<?php
session_start();
require_once "db.php";
require_once "audit.php";

function loginFailed(string $reason = "invalid"): void
{
    $_SESSION["failed_logins"] = ($_SESSION["failed_logins"] ?? 0) + 1;

    if ($_SESSION["failed_logins"] >= 5) {
        $_SESSION["lock_until"] = time() + 300;
        $_SESSION["failed_logins"] = 0;
    }

    header("Location: login.php?error=" . $reason);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

if (!empty($_SESSION["lock_until"]) && time() < $_SESSION["lock_until"]) {
    header("Location: login.php?error=locked");
    exit;
}

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";

if ($email === "" || $password === "") {
    header("Location: login.php?error=empty");
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, name, email, password, role, status
     FROM users
     WHERE LOWER(TRIM(email)) = LOWER(TRIM(?))
     LIMIT 1"
);

if (!$stmt) {
    error_log("Login query failed: " . $conn->error);
    header("Location: login.php?error=invalid");
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user["password"])) {
    loginFailed("invalid");
}

if (!in_array($user["role"], ["Admin", "Student"], true)) {
    loginFailed("invalid");
}

if (($user["status"] ?? "active") !== "active") {
    header("Location: login.php?error=inactive");
    exit;
}

unset($_SESSION["failed_logins"], $_SESSION["lock_until"]);
session_regenerate_id(true);

$_SESSION["user_id"] = $user["id"];
$_SESSION["name"] = $user["name"];
$_SESSION["email"] = $user["email"];
$_SESSION["last_activity"] = time();
writeAuditLog($conn, "LOGIN_SUCCESS", "User logged in: " . $user["email"]);$_SESSION["role"] = $user["role"];


if ($user["role"] === "Admin") {
    header("Location: admin_dashboard.php");
} else {
    header("Location: student_dashboard.php");
}
exit;
?>