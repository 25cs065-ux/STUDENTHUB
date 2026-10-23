```php
<?php
session_start();
require_once "events_db.php";
require_once "audit.php";

function redirectWithMessage($message) {
    header("Location: events.php?message=" . urlencode($message));
    exit;
}

if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

if (
    !isset($_SESSION["last_activity"]) ||
    (time() - $_SESSION["last_activity"]) > 900
) {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php?error=timeout");
    exit;
}

if ($_SESSION["role"] !== "Admin") {
    header("Location: student_dashboard.php?error=unauthorized");
    exit;
}

$_SESSION["last_activity"] = time();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: events.php");
    exit;
}

if (
    !isset($_POST["csrf_token"], $_SESSION["event_csrf_token"]) ||
    !hash_equals($_SESSION["event_csrf_token"], $_POST["csrf_token"])
) {
    redirectWithMessage("csrf");
}

$id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    redirectWithMessage("invalid_id");
}

$stmt = $conn->prepare("SELECT poster FROM events WHERE event_id = ?");

if (!$stmt) {
    redirectWithMessage("db_error");
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();
$stmt->close();

if (!$event) {
    redirectWithMessage("not_found");
}

$stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");

if (!$stmt) {
    redirectWithMessage("delete_failed");
}

$stmt->bind_param("i", $id);
$success = $stmt->execute();
$stmt->close();

if (!$success) {
    redirectWithMessage("delete_failed");
}

$poster = $event["poster"] ?? "";

if (strpos($poster, "uploads/events/") === 0) {
    $filename = substr($poster, strlen("uploads/events/"));

    if (
        $filename !== "" &&
        basename($filename) === $filename &&
        preg_match('/\A[a-f0-9]{32}\.(jpg|png|webp)\z/', $filename)
    ) {
        $posterPath = __DIR__ . DIRECTORY_SEPARATOR
            . "uploads" . DIRECTORY_SEPARATOR
            . "events" . DIRECTORY_SEPARATOR
            . $filename;

        if (is_file($posterPath)) {
            unlink($posterPath);
        }
    }
}

writeAuditLog($conn, "EVENT_DELETED", "Deleted event ID: " . $id);
redirectWithMessage("deleted");
?>
```