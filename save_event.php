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

$title = trim($_POST["title"] ?? "");
$description = trim($_POST["description"] ?? "");
$eventDate = trim($_POST["event_date"] ?? "");
$venue = trim($_POST["venue"] ?? "");
$status = $_POST["status"] ?? "Upcoming";
$id = 0;

if (isset($_POST["id"]) && $_POST["id"] !== "") {
    $id = filter_var($_POST["id"], FILTER_VALIDATE_INT);

    if ($id === false || $id < 1) {
        redirectWithMessage("invalid_id");
    }
}

if (
    $title === "" ||
    $description === "" ||
    $eventDate === "" ||
    $venue === "" ||
    strlen($title) > 100 ||
    strlen($description) > 5000 ||
    strlen($venue) > 200 ||
    !in_array($status, ["Upcoming", "Ongoing", "Completed", "Cancelled"], true)
) {
    redirectWithMessage("invalid_data");
}

$dateObject = DateTime::createFromFormat("!Y-m-d\TH:i", $eventDate);
$dateErrors = DateTime::getLastErrors();

if (
    !$dateObject ||
    ($dateErrors !== false &&
        ($dateErrors["warning_count"] > 0 || $dateErrors["error_count"] > 0)) ||
    $dateObject->format("Y-m-d\TH:i") !== $eventDate
) {
    redirectWithMessage("invalid_data");
}

$eventDate = $dateObject->format("Y-m-d H:i:s");

$currentPoster = "";

if ($id > 0) {
    $stmt = $conn->prepare("SELECT poster FROM events WHERE event_id = ?");

    if (!$stmt) {
        redirectWithMessage("db_error");
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        redirectWithMessage("not_found");
    }

    $currentPoster = $existing["poster"] ?? "";
}

$newPoster = $currentPoster;
$uploadedFile = isset($_FILES["poster"]) &&
    $_FILES["poster"]["error"] !== UPLOAD_ERR_NO_FILE;

if ($uploadedFile) {
    $file = $_FILES["poster"];

    if ($file["error"] !== UPLOAD_ERR_OK) {
        if ($file["error"] === UPLOAD_ERR_INI_SIZE ||
            $file["error"] === UPLOAD_ERR_FORM_SIZE) {
            redirectWithMessage("file_too_large");
        }

        redirectWithMessage("upload_failed");
    }

    if (!is_uploaded_file($file["tmp_name"])) {
        redirectWithMessage("upload_failed");
    }

    if ($file["size"] <= 0 || $file["size"] > 2 * 1024 * 1024) {
        redirectWithMessage("file_too_large");
    }

    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file["tmp_name"]);

    if (!isset($allowedTypes[$mimeType])) {
        redirectWithMessage("invalid_type");
    }

    $imageInfo = @getimagesize($file["tmp_name"]);

    if (
        $imageInfo === false ||
        !isset($imageInfo["mime"]) ||
        $imageInfo["mime"] !== $mimeType
    ) {
        redirectWithMessage("invalid_type");
    }

    $uploadDirectory = __DIR__ . DIRECTORY_SEPARATOR . "uploads"
        . DIRECTORY_SEPARATOR . "events";

    if (
        !is_dir($uploadDirectory) &&
        !mkdir($uploadDirectory, 0755, true)
    ) {
        redirectWithMessage("upload_failed");
    }

    $filename = bin2hex(random_bytes(16)) . "." . $allowedTypes[$mimeType];
    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        redirectWithMessage("upload_failed");
    }

    $newPoster = "uploads/events/" . $filename;
}

if ($id > 0) {
    $stmt = $conn->prepare(
        "UPDATE events
         SET event_name = ?, description = ?, event_date = ?, venue = ?, status = ?, poster = ? WHERE event_id = ?"
    );

    if (!$stmt) {
        if ($uploadedFile && isset($destination) && is_file($destination)) {
            unlink($destination);
        }

        redirectWithMessage("save_failed");
    }

    $stmt->bind_param(
        "ssssssi",
        $title,
        $description,
        $eventDate,
        $venue,
        $status,
        $newPoster,
        $id
    );

    $success = $stmt->execute();
    $stmt->close();

    if (!$success) {
        if ($uploadedFile && isset($destination) && is_file($destination)) {
            unlink($destination);
        }

        redirectWithMessage("save_failed");
    }

    if (
        $uploadedFile &&
        $currentPoster !== "" &&
        strpos($currentPoster, "uploads/events/") === 0
    ) {
        $oldPosterPath = __DIR__ . DIRECTORY_SEPARATOR
            . str_replace("/", DIRECTORY_SEPARATOR, $currentPoster);

        if (is_file($oldPosterPath)) {
            unlink($oldPosterPath);
        }
    }

    writeAuditLog($conn, "EVENT_UPDATED", "Updated event ID: " . $id . ", Title: " . $title);
    redirectWithMessage("updated");
} else {
    $stmt = $conn->prepare(
        "INSERT INTO events (event_name, description, event_date, venue, status, poster)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        if ($uploadedFile && isset($destination) && is_file($destination)) {
            unlink($destination);
        }

        redirectWithMessage("save_failed");
    }

    $stmt->bind_param(
        "ssssss",
        $title,
        $description,
        $eventDate,
        $venue,
        $status,
        $newPoster
    );

    $success = $stmt->execute();
    $stmt->close();

    if (!$success) {
        if ($uploadedFile && isset($destination) && is_file($destination)) {
            unlink($destination);
        }

        redirectWithMessage("save_failed");
    }

    writeAuditLog($conn, "EVENT_CREATED", "Created event: " . $title);
    redirectWithMessage("created");
}
?>
```