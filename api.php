
<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store");

function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
    respond(401, ["success" => false, "message" => "Please log in first."]);
}

if (
    !isset($_SESSION["last_activity"]) ||
    time() - $_SESSION["last_activity"] > 900
) {
    $_SESSION = [];
    session_destroy();
    respond(401, ["success" => false, "message" => "Session expired. Please log in again."]);
}

if ($_SESSION["role"] !== "Admin") {
    respond(403, ["success" => false, "message" => "Administrator access required."]);
}

$_SESSION["last_activity"] = time();

if (empty($_SESSION["event_csrf_token"])) {
    $_SESSION["event_csrf_token"] = bin2hex(random_bytes(32));
}

require_once "events_db.php";

function readInput() {
    $input = json_decode(file_get_contents("php://input"), true);
    return is_array($input) ? $input : [];
}

function validateEvent($data) {
    $name = trim($data["event_name"] ?? "");
    $description = trim($data["description"] ?? "");
    $venue = trim($data["venue"] ?? "");
    $status = $data["status"] ?? "Upcoming";
    $date = trim($data["event_date"] ?? "");

    if (
        $name === "" || strlen($name) > 100 ||
        $description === "" || strlen($description) > 5000 ||
        $venue === "" || strlen($venue) > 200 ||
        !in_array($status, ["Upcoming", "Ongoing", "Completed", "Cancelled"], true)
    ) {
        respond(422, ["success" => false, "message" => "Please enter valid event details."]);
    }

    $date = str_replace("T", " ", $date);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $date)) {
        $date .= ":00";
    }

    $dateObject = DateTime::createFromFormat("!Y-m-d H:i:s", $date);
    $dateErrors = DateTime::getLastErrors();

    if (
        !$dateObject ||
        ($dateErrors !== false &&
        ($dateErrors["warning_count"] > 0 || $dateErrors["error_count"] > 0)) ||
        $dateObject->format("Y-m-d H:i:s") !== $date
    ) {
        respond(422, ["success" => false, "message" => "Please enter a valid event date and time."]);
    }

    return [$name, $description, $date, $venue, $status];
}

function checkCsrf() {
    $token = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";

    if (
        $token === "" ||
        !isset($_SESSION["event_csrf_token"]) ||
        !hash_equals($_SESSION["event_csrf_token"], $token)
    ) {
        respond(403, ["success" => false, "message" => "Invalid or expired security token. Refresh the page and try again."]);
    }
}

function getEventId() {
    $data = readInput();
    $id = filter_var($data["event_id"] ?? "", FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        respond(422, ["success" => false, "message" => "A valid event ID is required."]);
    }

    return [$id, $data];
}

function removePoster($poster) {
    if (
        !is_string($poster) ||
        strpos($poster, "uploads/events/") !== 0
    ) {
        return;
    }

    $filename = substr($poster, strlen("uploads/events/"));

    if (!preg_match('/\A[a-f0-9]{32}\.(jpg|png|webp)\z/', $filename)) {
        return;
    }

    $path = __DIR__ . DIRECTORY_SEPARATOR . "uploads"
        . DIRECTORY_SEPARATOR . "events"
        . DIRECTORY_SEPARATOR . $filename;

    if (is_file($path)) {
        @unlink($path);
    }
}

$method = $_SERVER["REQUEST_METHOD"];

try {
    if ($method === "GET") {
        $result = $conn->query(
            "SELECT event_id, event_name, description, event_date, venue, status, poster
             FROM events ORDER BY event_date DESC, event_id DESC"
        );

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        respond(200, [
            "success" => true,
            "csrf_token" => $_SESSION["event_csrf_token"],
            "data" => $events
        ]);
    }

    if (!in_array($method, ["POST", "PUT", "DELETE"], true)) {
        header("Allow: GET, POST, PUT, DELETE");
        respond(405, ["success" => false, "message" => "HTTP method not allowed."]);
    }

    checkCsrf();

    if ($method === "POST") {
        $data = readInput();
        [$name, $description, $date, $venue, $status] = validateEvent($data);

        $stmt = $conn->prepare(
            "INSERT INTO events (event_name, description, event_date, venue, status)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssss", $name, $description, $date, $venue, $status);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();

        respond(201, ["success" => true, "message" => "Event added successfully.", "event_id" => $id]);
    }

    if ($method === "PUT") {
        [$id, $data] = getEventId();
        [$name, $description, $date, $venue, $status] = validateEvent($data);

        $stmt = $conn->prepare("SELECT event_id FROM events WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$exists) {
            respond(404, ["success" => false, "message" => "Event not found."]);
        }

        $stmt = $conn->prepare(
            "UPDATE events
             SET event_name = ?, description = ?, event_date = ?, venue = ?, status = ?
             WHERE event_id = ?"
        );
        $stmt->bind_param("sssssi", $name, $description, $date, $venue, $status, $id);
        $stmt->execute();
        $stmt->close();

        respond(200, ["success" => true, "message" => "Event updated successfully."]);
    }

    if ($method === "DELETE") {
        [$id] = getEventId();

        $stmt = $conn->prepare("SELECT poster FROM events WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$event) {
            respond(404, ["success" => false, "message" => "Event not found."]);
        }

        $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        removePoster($event["poster"] ?? null);

        respond(200, ["success" => true, "message" => "Event deleted successfully."]);
    }
} catch (Throwable $e) {
    error_log("Events API error: " . $e->getMessage());
    respond(500, ["success" => false, "message" => "A server or database error occurred."]);
}
