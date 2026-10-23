<?php
session_start();
require_once "db.php";
require_once "audit.php";

if (!isset($_SESSION["user_id"], $_SESSION["role"])) {
    header("Location: login.php?error=unauthorized");
    exit;
}

if (!isset($_SESSION["last_activity"]) || (time() - $_SESSION["last_activity"]) > 900) {
    header("Location: timeout.php");
    exit;
}

if ($_SESSION["role"] !== "Student") {
    header("Location: admin_dashboard.php?error=unauthorized");
    exit;
}

$_SESSION["last_activity"] = time();

if (empty($_SESSION["student_csrf"])) {
    $_SESSION["student_csrf"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$studentId = (int) $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION["student_csrf"], $_POST["csrf_token"] ?? "")) {
        header("Location: student_events.php?msg=csrf");
        exit;
    }

    $eventId = filter_input(INPUT_POST, "event_id", FILTER_VALIDATE_INT);
    $action = $_POST["action"] ?? "";

    if (!$eventId || !in_array($action, ["register", "cancel"], true)) {
        header("Location: student_events.php?msg=invalid");
        exit;
    }

    $stmt = $conn->prepare("SELECT status FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$event) {
        header("Location: student_events.php?msg=notfound");
        exit;
    }

    if ($action === "register") {
        if ($event["status"] !== "Upcoming") {
            header("Location: student_events.php?msg=closed");
            exit;
        }

        $stmt = $conn->prepare(
            "INSERT INTO registrations (student_id, event_id) VALUES (?, ?)"
        );
        $stmt->bind_param("ii", $studentId, $eventId);
        $ok = $stmt->execute();
        $errno = $stmt->errno;
        $stmt->close();

        if ($ok) {
            writeAuditLog($conn, "EVENT_REGISTERED", "Registered for event ID: " . $eventId);
            header("Location: student_events.php?msg=registered");
        } elseif ($errno === 1062) {
            header("Location: student_events.php?msg=already");
        } else {
            header("Location: student_events.php?msg=failed");
        }
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM registrations WHERE student_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $studentId, $eventId);
    $stmt->execute();
    $removed = $stmt->affected_rows;
    $stmt->close();

    if ($removed > 0) {
        writeAuditLog($conn, "EVENT_REGISTRATION_CANCELLED", "Cancelled registration for event ID: " . $eventId);
    }

    header("Location: student_events.php?msg=cancelled");
    exit;
}

$messages = [
    "registered" => ["success", "You have registered for the event."],
    "cancelled"  => ["success", "Your registration has been cancelled."],
    "already"    => ["error", "You are already registered for this event."],
    "closed"     => ["error", "Registration is open only for upcoming events."],
    "notfound"   => ["error", "Event not found."],
    "invalid"    => ["error", "Invalid request."],
    "csrf"       => ["error", "Invalid request. Please refresh the page and try again."],
    "failed"     => ["error", "Something went wrong. Please try again."]
];

$message = $messages[$_GET["msg"] ?? ""] ?? null;

$stmt = $conn->prepare(
    "SELECT e.event_id, e.event_name, e.event_date, e.venue, e.description,
            e.status, e.poster, r.registration_id
     FROM events e
     LEFT JOIN registrations r
            ON r.event_id = e.event_id AND r.student_id = ?
     ORDER BY e.event_date ASC"
);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$events = $stmt->get_result();
$stmt->close();

$name = $_SESSION["name"] ?? "Student";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events | STUDENTHUB</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .events-wrap { max-width: 1100px; }
        .events-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-top: 16px; }
        .event-box { border: 1px solid #dbe4ee; border-radius: 12px; overflow: hidden; background: #fff; }
        .event-box img { width: 100%; height: 170px; object-fit: cover; display: block; }
        .no-poster { height: 170px; display: flex; align-items: center; justify-content: center; background: #eff6ff; color: #64748b; }
        .event-body { padding: 16px; }
        .event-body h3 { margin: 0 0 8px; overflow-wrap: anywhere; }
        .event-body p { margin: 6px 0; }
        .tag { display: inline-block; padding: 3px 10px; border-radius: 20px; background: #eff6ff; color: #1d4ed8; font-size: 13px; font-weight: 600; }
        .tag.joined { background: #dcfce7; color: #166534; margin-left: 6px; }
        .event-body form { margin-top: 12px; }
        .btn-cancel { background: #b91c1c; }
        .btn-cancel:hover { background: #991b1b; }
    </style>
</head>
<body>
<main class="container events-wrap">
    <h1>Campus Events</h1>
    <p class="description">Welcome, <?= e($name) ?>. Register for upcoming events below.</p>

    <p>
        <a class="dashboard-link" href="student_dashboard.php">Back to Dashboard</a>
        <a class="dashboard-link" href="logout.php">Logout</a>
    </p>

    <?php if ($message !== null): ?>
        <div class="notice-message <?= e($message[0]) ?>" role="status">
            <?= e($message[1]) ?>
        </div>
    <?php endif; ?>

    <?php if ($events->num_rows === 0): ?>
        <p>No events are available right now.</p>
    <?php else: ?>
        <div class="events-grid">
            <?php while ($row = $events->fetch_assoc()): ?>
                <?php $joined = !empty($row["registration_id"]); ?>
                <article class="event-box">
                    <?php if (!empty($row["poster"])): ?>
                        <img src="<?= e($row["poster"]) ?>" alt="<?= e($row["event_name"]) ?> poster">
                    <?php else: ?>
                        <div class="no-poster">No poster uploaded</div>
                    <?php endif; ?>

                    <div class="event-body">
                        <h3><?= e($row["event_name"]) ?></h3>
                        <p><strong>Date:</strong> <?= e(date("d M Y, h:i A", strtotime($row["event_date"]))) ?></p>
                        <p><strong>Venue:</strong> <?= e($row["venue"]) ?></p>
                        <p>
                            <span class="tag"><?= e($row["status"]) ?></span>
                            <?php if ($joined): ?><span class="tag joined">Registered</span><?php endif; ?>
                        </p>
                        <p><?= nl2br(e($row["description"] ?? "")) ?></p>

                        <?php if ($joined): ?>
                            <form method="post" action="student_events.php"
                                  onsubmit="return confirm('Cancel your registration for this event?');">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION["student_csrf"]) ?>">
                                <input type="hidden" name="event_id" value="<?= (int)$row["event_id"] ?>">
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn-cancel">Cancel Registration</button>
                            </form>
                        <?php elseif ($row["status"] === "Upcoming"): ?>
                            <form method="post" action="student_events.php">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION["student_csrf"]) ?>">
                                <input type="hidden" name="event_id" value="<?= (int)$row["event_id"] ?>">
                                <input type="hidden" name="action" value="register">
                                <button type="submit">Register</button>
                            </form>
                        <?php else: ?>
                            <p><em>Registration closed.</em></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
<?php $conn->close(); ?>