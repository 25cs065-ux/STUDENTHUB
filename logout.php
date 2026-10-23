<?php
session_start();
require_once "db.php";
require_once "audit.php";

if (isset($_SESSION["user_id"])) {
    writeAuditLog($conn, "LOGOUT", "User logged out: " . ($_SESSION["email"] ?? ""));
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

header("Location: login.php?error=loggedout");
exit;
?>