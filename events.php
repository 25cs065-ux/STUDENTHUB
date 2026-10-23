<?php
session_start();
require_once "events_db.php";

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

if (empty($_SESSION["event_csrf_token"])) {
    $_SESSION["event_csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$editing = false;

$event = [
    "event_id" => "",
    "event_name" => "",
    "description" => "",
    "event_date" => date("Y-m-d H:i:s", strtotime("+1 day")),
    "venue" => "",
    "status" => "Upcoming",
    "poster" => ""
];

$messages = [
    "created" => ["success", "Event created successfully."],
    "updated" => ["success", "Event updated successfully."],
    "deleted" => ["success", "Event deleted successfully."],
    "invalid_type" => ["error", "Invalid poster type. Upload a JPG, PNG or WebP image."],
    "file_too_large" => ["error", "Poster size must not exceed 2 MB."],
    "upload_failed" => ["error", "Poster upload failed. Please try again."],
    "invalid_data" => ["error", "Please check the event details and try again."],
    "invalid_id" => ["error", "Invalid event ID."],
    "not_found" => ["error", "The requested event was not found."],
    "csrf" => ["error", "Invalid form request. Please try again."],
    "save_failed" => ["error", "Unable to save the event."],
    "delete_failed" => ["error", "Unable to delete the event."],
    "db_error" => ["error", "A database error occurred."],
    "csrf_error" => ["error", "Your form session has expired. Please try again."]
];

$message = null;
$messageKey = $_GET["message"] ?? "";

if (isset($messages[$messageKey])) {
    $message = $messages[$messageKey];
}

if (isset($_GET["error"])) {
    $errorKey = $_GET["error"];

    if ($errorKey === "invalid_id" || $errorKey === "not_found") {
        $message = $messages[$errorKey];
    }
}

if (isset($_GET["edit"])) {
    $editId = filter_var($_GET["edit"], FILTER_VALIDATE_INT);

    if (!$editId || $editId < 1) {
        header("Location: events.php?message=invalid_id");
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT event_id, event_name, description, event_date, venue, status, poster
         FROM events
         WHERE event_id = ?"
    );

    if (!$stmt) {
        error_log("Event lookup failed: " . $conn->error);
        http_response_code(500);
        exit("Unable to load the event.");
    }

    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $existingEvent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existingEvent) {
        header("Location: events.php?message=not_found");
        exit;
    }

    $event = $existingEvent;
    $editing = true;
}

$stmt = $conn->prepare(
    "SELECT event_id, event_name, description, event_date, venue, status, poster
     FROM events
     ORDER BY event_date DESC, event_id DESC"
);

if (!$stmt) {
    error_log("Event list query failed: " . $conn->error);
    http_response_code(500);
    exit("Unable to load events. Please check the database.");
}

$stmt->execute();
$events = $stmt->get_result();
$stmt->close();

$name = $_SESSION["name"] ?? "Administrator";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management | StudentHub</title>
    <link rel="stylesheet" href="style.css">

    <style>
        .event-page {
            max-width: 1200px;
            margin: 25px auto;
            padding: 20px;
        }

        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
        }

        .event-header h1 {
            text-align: left;
            margin: 0;
        }

        .event-button {
            display: inline-block;
            padding: 10px 16px;
            border: 0;
            border-radius: 7px;
            background: #315b7d;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            font: inherit;
            font-weight: 600;
        }

        .event-button:hover {
            background: #244560;
            color: #fff;
        }

        .event-button.danger {
            background: #b91c1c;
        }

        .event-card {
            background: #fff;
            border: 1px solid #dbe4ee;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
        }

        .event-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .event-form-grid .full-width {
            grid-column: 1 / -1;
        }

        .event-form-grid input,
        .event-form-grid textarea,
        .event-form-grid select {
            box-sizing: border-box;
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
            background: #fff;
        }

        .event-form-grid textarea {
            min-height: 110px;
            resize: vertical;
        }

        .event-form-grid input[type="file"] {
            padding: 9px;
        }

        .event-form-grid label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
        }

        .event-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .event-item {
            overflow: hidden;
            border: 1px solid #dbe4ee;
            border-radius: 12px;
            background: #fff;
        }

        .event-poster {
            display: block;
            width: 100%;
            height: 190px;
            object-fit: cover;
            background: #f1f5f9;
        }

        .event-poster-placeholder {
            height: 190px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #64748b;
        }

        .current-poster {
            display: block;
            width: 130px;
            max-height: 110px;
            object-fit: cover;
            border-radius: 8px;
            margin: 8px 0;
        }

        .event-item-content {
            padding: 18px;
        }

        .event-item-content h3 {
            margin-top: 0;
            overflow-wrap: anywhere;
        }

        .event-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .event-actions form {
            margin: 0;
        }

        .event-message {
            padding: 13px 16px;
            border-radius: 8px;
            margin: 16px 0;
            font-weight: 600;
        }

        .event-message.success {
            color: #166534;
            background: #dcfce7;
            border: 1px solid #86efac;
        }

        .event-message.error {
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fca5a5;
        }

        .event-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 13px;
            font-weight: 600;
        }

        .event-empty {
            padding: 30px;
            text-align: center;
            color: #64748b;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
        }

        @media (max-width: 650px) {
            .event-form-grid {
                grid-template-columns: 1fr;
            }

            .event-form-grid .full-width {
                grid-column: auto;
            }

            .event-card {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
<main class="container event-page">

    <header class="event-header">
        <div>
            <h1>Event Management</h1>
            <p>Welcome, <?= e($name) ?>. Manage StudentHub events and posters.</p>
        </div>

        <a class="event-button" href="admin_dashboard.php">
            Back to Admin Dashboard
        </a>
    </header>

    <?php if ($message !== null): ?>
        <div class="event-message <?= e($message[0]) ?>" role="alert">
            <?= e($message[1]) ?>
        </div>
    <?php endif; ?>

    <section class="event-card" id="event-form">
        <h2><?= $editing ? "Update Event" : "Create New Event" ?></h2>

        <p>Enter the event details and upload an optional poster. Maximum poster size: 2 MB.</p>

        <form action="save_event.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION["event_csrf_token"]) ?>">
            <input type="hidden" name="id" value="<?= e($event["event_id"]) ?>">

            <div class="event-form-grid">

                <div>
                    <label for="title">Event Name *</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        maxlength="100"
                        value="<?= e($event["event_name"]) ?>"
                        required
                    >
                </div>

                <div>
                    <label for="event_date">Event Date and Time *</label>
                    <input
                        type="datetime-local"
                        id="event_date"
                        name="event_date"
                        value="<?= e(date("Y-m-d\TH:i", strtotime($event["event_date"]))) ?>"
                        required
                    >
                </div>

                <div>
                    <label for="venue">Venue *</label>
                    <input
                        type="text"
                        id="venue"
                        name="venue"
                        maxlength="200"
                        value="<?= e($event["venue"]) ?>"
                        required
                    >
                </div>

                <div>
                    <label for="status">Event Status *</label>
                    <select id="status" name="status" required>
                        <?php foreach (["Upcoming", "Ongoing", "Completed", "Cancelled"] as $statusOption): ?>
                            <option
                                value="<?= e($statusOption) ?>"
                                <?= $event["status"] === $statusOption ? "selected" : "" ?>
                            >
                                <?= e($statusOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="full-width">
                    <label for="description">Description *</label>
                    <textarea
                        id="description"
                        name="description"
                        maxlength="5000"
                        required
                    ><?= e($event["description"]) ?></textarea>
                </div>

                <div class="full-width">
                    <label for="poster">
                        Event Poster <?= $editing ? "(optional if keeping the current poster)" : "(optional)" ?>
                    </label>

                    <input
                        type="file"
                        id="poster"
                        name="poster"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >

                    <?php if ($editing && !empty($event["poster"])): ?>
                        <p>Current poster:</p>
                        <img
                            class="current-poster"
                            src="<?= e($event["poster"]) ?>"
                            alt="Current event poster"
                        >
                    <?php endif; ?>

                    <small>Allowed: JPG, JPEG, PNG and WebP. Maximum 2 MB.</small>
                </div>

                <div class="full-width">
                    <button type="submit" class="event-button">
                        <?= $editing ? "Update Event" : "Create Event" ?>
                    </button>

                    <?php if ($editing): ?>
                        <a class="event-button" href="events.php">Cancel Edit</a>
                    <?php endif; ?>
                </div>

            </div>
        </form>
    </section>

    <section class="event-card">
        <h2>All Events</h2>
        <p>Events currently stored in the StudentHub database.</p>

        <?php if ($events->num_rows === 0): ?>
            <div class="event-empty">
                No events found. Create your first event using the form above.
            </div>
        <?php else: ?>
            <div class="event-list">

                <?php while ($row = $events->fetch_assoc()): ?>
                    <article class="event-item">

                        <?php if (!empty($row["poster"])): ?>
                            <img
                                class="event-poster"
                                src="<?= e($row["poster"]) ?>"
                                alt="<?= e($row["event_name"]) ?> poster"
                            >
                        <?php else: ?>
                            <div class="event-poster-placeholder">
                                No poster uploaded
                            </div>
                        <?php endif; ?>

                        <div class="event-item-content">
                            <h3><?= e($row["event_name"]) ?></h3>

                            <p>
                                <strong>Date:</strong>
                                <?= e(date("d M Y, h:i A", strtotime($row["event_date"]))) ?>
                            </p>

                            <p><strong>Venue:</strong> <?= e($row["venue"]) ?></p>

                            <p>
                                <span class="event-status"><?= e($row["status"]) ?></span>
                            </p>

                            <p><?= nl2br(e($row["description"] ?? "")) ?></p>

                            <div class="event-actions">
                                <a
                                    class="event-button"
                                    href="events.php?edit=<?= (int)$row["event_id"] ?>#event-form"
                                >
                                    Edit
                                </a>

                                <form
                                    action="delete_event.php"
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this event?');"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= e($_SESSION["event_csrf_token"]) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int)$row["event_id"] ?>"
                                    >

                                    <button type="submit" class="event-button danger">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>

            </div>
        <?php endif; ?>
    </section>

    <p><a href="logout.php">Logout</a></p>

</main>
</body>
</html>
