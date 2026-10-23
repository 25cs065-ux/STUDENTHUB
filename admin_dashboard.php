
<?php
session_start();
require_once "db.php";
require_once "audit.php";

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
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>403 Forbidden | STUDENTHUB</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <main class="container text-center py-5">
            <h1 class="display-1 fw-bold text-danger">403</h1>
            <h2>Access Forbidden</h2>
            <p>You do not have permission to access the Admin Dashboard.</p>
            <a class="btn btn-primary" href="student_dashboard.php">Return to Student Dashboard</a>
        </main>
    </body>
    </html>
    <?php
    exit;
}

$_SESSION["last_activity"] = time();

$name = htmlspecialchars($_SESSION["name"] ?? "Administrator", ENT_QUOTES, "UTF-8");
$email = htmlspecialchars($_SESSION["email"] ?? "", ENT_QUOTES, "UTF-8");

$totalUsers = 0;
$totalStudents = 0;
$totalEvents = 0;
$totalRegistrations = 0;
$latestRegistrations = [];
$errors = [];

$result = $conn->query("SELECT COUNT(*) AS total FROM users");
if ($result) { $totalUsers = (int) $result->fetch_assoc()["total"]; }
else { $errors[] = "Could not load user count."; }

$result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'Student' AND status = 'active'");
if ($result) { $totalStudents = (int) $result->fetch_assoc()["total"]; }
else { $errors[] = "Could not load student count."; }

$result = $conn->query("SELECT COUNT(*) AS total FROM events");
if ($result) { $totalEvents = (int) $result->fetch_assoc()["total"]; }
else { $errors[] = "Could not load event count."; }

$result = $conn->query("SELECT COUNT(*) AS total FROM registrations");
if ($result) { $totalRegistrations = (int) $result->fetch_assoc()["total"]; }
else { $errors[] = "Could not load registration count."; }

$sql = "SELECT r.registration_id, u.name AS student_name,
               e.event_name, r.registration_date, r.status
        FROM registrations r
        LEFT JOIN users u ON r.student_id = u.id
        LEFT JOIN events e ON r.event_id = e.event_id
        ORDER BY r.registration_date DESC, r.registration_id DESC
        LIMIT 5";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $latestRegistrations[] = $row;
    }
} else {
    $errors[] = "Could not load latest registrations.";
}

writeAuditLog($conn, "ADMIN_DASHBOARD_VIEW", "Admin opened the dashboard.");

function h($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | STUDENTHUB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7fb; color: #243447; }
        .sidebar { background: #183b56; min-height: 100vh; }
        .sidebar a { display: block; color: #fff; text-decoration: none; padding: 12px 16px; border-radius: 8px; margin-bottom: 6px; }
        .sidebar a:hover, .sidebar a.active { background: #2e607d; }
        .stat-card { border: 0; border-radius: 16px; box-shadow: 0 4px 18px rgba(20,50,80,.07); height: 100%; }
        .stat-number { font-size: 2rem; font-weight: 700; }
        .panel { border: 0; border-radius: 16px; box-shadow: 0 4px 18px rgba(20,50,80,.07); }
        .small-label { color: #64748b; font-size: .9rem; }
        @media (max-width: 767px) { .sidebar { min-height: auto; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-md-3 col-lg-2 sidebar p-3">
            <h3 class="text-white mb-1">STUDENTHUB</h3>
            <p class="text-white-50 small mb-4">Administration</p>
            <a class="active" href="admin_dashboard.php">Dashboard</a>
            <a href="manage_students.php">Manage Students</a>
            <a href="events.php">Manage Events</a>
            <a href="logout.php">Logout</a>
        </aside>

        <main class="col-md-9 col-lg-10 px-4 py-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <div>
                    <h1 class="h2 mb-1">Admin Dashboard</h1>
                    <p class="text-secondary mb-0">Welcome, <?= $name ?>!</p>
                </div>
                <div class="text-md-end">
                    <div class="fw-semibold"><?= $email ?></div>
                    <span class="badge text-bg-primary">Administrator</span>
                </div>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-warning">
                    <strong>Some dashboard data could not be loaded:</strong>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= h($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card"><div class="card-body">
                        <div class="small-label">Total Users</div>
                        <div class="stat-number"><?= $totalUsers ?></div>
                        <div class="text-secondary small">Registered accounts</div>
                    </div></div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card"><div class="card-body">
                        <div class="small-label">Total Students</div>
                        <div class="stat-number"><?= $totalStudents ?></div>
                        <div class="text-secondary small">Student records</div>
                    </div></div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card"><div class="card-body">
                        <div class="small-label">Total Events</div>
                        <div class="stat-number"><?= $totalEvents ?></div>
                        <div class="text-secondary small">Available event records</div>
                    </div></div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card stat-card"><div class="card-body">
                        <div class="small-label">Registrations</div>
                        <div class="stat-number"><?= $totalRegistrations ?></div>
                        <div class="text-secondary small">Event registrations</div>
                    </div></div>
                </div>
            </div>

            <section class="card panel">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h2 class="h5 mb-1">Latest Registrations</h2>
                            <p class="text-secondary small mb-0">Most recent event registrations</p>
                        </div>
                        <a href="events.php" class="btn btn-outline-primary btn-sm">Manage Events</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Registration ID</th>
                                    <th>Student</th>
                                    <th>Event</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!$latestRegistrations): ?>
                                <tr><td colspan="5" class="text-center text-secondary py-4">No registrations found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($latestRegistrations as $registration): ?>
                                    <tr>
                                        <td><?= h($registration["registration_id"]) ?></td>
                                        <td><?= h($registration["student_name"] ?: "Student record unavailable") ?></td>
                                        <td><?= h($registration["event_name"] ?: "Event record unavailable") ?></td>
                                        <td><?= h($registration["registration_date"]) ?></td>
                                        <td><span class="badge text-bg-info"><?= h($registration["status"]) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <p class="text-secondary small mt-4 mb-0">STUDENTHUB · Admin panel · Data loaded from MySQL</p>
        </main>
    </div>
</div>
</body>
</html>
