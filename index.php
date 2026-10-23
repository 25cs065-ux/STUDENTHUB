<?php
session_start();

if (isset($_SESSION["user_id"], $_SESSION["role"])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit;
?>