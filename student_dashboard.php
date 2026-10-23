<?php
session_start();
require_once "db.php";

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

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

$studentId = (int) $_SESSION["user_id"];
$name = $_SESSION["name"] ?? "Student";
$email = $_SESSION["email"] ?? "";

$stmt = $conn->prepare(
    "SELECT e.event_name, e.event_date, e.venue
     FROM registrations r
     JOIN events e ON e.event_id = r.event_id
     WHERE r.student_id = ?
     ORDER BY e.event_date ASC
     LIMIT 5"
);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$myEvents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM registrations WHERE student_id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$totalRegistered = (int) $stmt->get_result()->fetch_assoc()["total"];
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | STUDENTHUB</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container" style="max-width:800px;">
    <h1>Student Dashboard</h1>
    <p class="description">Welcome, <?= e($name) ?>!</p>

    <div class="dashboard-card">
        <p><strong>Email:</strong> <?= e($email) ?></p>
        <p><strong>Role:</strong> Student</p>
        <p><strong>Events registered:</strong> <?= $totalRegistered ?></p>
    </div>

    <h2>Quick Links</h2>
    <p>
        <a class="dashboard-link" href="student_events.php">Browse &amp; Register for Events</a>
        <a class="dashboard-link" href="timetable.html">View Timetable</a>
        <a class="dashboard-link" href="logout.php">Logout</a>
    </p>

    <h2>My Registered Events</h2>
    <?php if (!$myEvents): ?>
        <p>You have not registered for any events yet.</p>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="student-table">
                <thead>
                    <tr><th>Event</th><th>Date</th><th>Venue</th></tr>
                </thead>
                <tbody>
                <?php foreach ($myEvents as $ev): ?>
                    <tr>
                        <td><?= e($ev["event_name"]) ?></td>
                        <td><?= e(date("d M Y, h:i A", strtotime($ev["event_date"]))) ?></td>
                        <td><?= e($ev["venue"]) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
</body>
</html>