
<?php
session_start();

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

$_SESSION["last_activity"] = time();

if ($_SESSION["role"] === "Admin") {
    header("Location: admin_dashboard.php");
    exit;
}

if ($_SESSION["role"] === "Student") {
    header("Location: student_dashboard.php");
    exit;
}

http_response_code(403);
exit("403 Forbidden: Access denied.");
?>
