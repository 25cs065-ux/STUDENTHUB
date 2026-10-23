
<?php
function writeAuditLog($conn, $action, $details = "")
{
    if (!isset($_SESSION["user_id"], $_SESSION["name"], $_SESSION["role"])) {
        return;
    }

    $userId = (int) $_SESSION["user_id"];
    $userName = $_SESSION["name"];
    $userRole = $_SESSION["role"];
    $ipAddress = $_SERVER["REMOTE_ADDR"] ?? "";

    $stmt = $conn->prepare(
        "INSERT INTO studenthub.audit_logs
        (user_id, user_name, user_role, action, details, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        error_log("Audit log preparation failed: " . $conn->error);
        return;
    }

    $stmt->bind_param(
        "isssss",
        $userId,
        $userName,
        $userRole,
        $action,
        $details,
        $ipAddress
    );

    if (!$stmt->execute()) {
        error_log("Audit log insertion failed: " . $stmt->error);
    }

    $stmt->close();
}
?>
