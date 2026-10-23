```php
<?php
session_start();
require_once "db.php";
require_once "audit.php";

if (!isset($_SESSION["user_id"], $_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: login.php?error=unauthorized");
    exit;
}

if (!isset($_SESSION["last_activity"]) || time() - $_SESSION["last_activity"] > 900) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=timeout");
    exit;
}

$_SESSION["last_activity"] = time();

$stmt = $conn->prepare(
    "SELECT id, name, email, mobile, course, year, gender, status, created_at
     FROM users
     WHERE role = 'Student'
     ORDER BY id ASC"
);

if (!$stmt || !$stmt->execute()) {
    http_response_code(500);
    exit("CSV export failed. Please try again.");
}

$result = $stmt->get_result();

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=student_records.csv");
header("Pragma: no-cache");
header("Expires: 0");

$output = fopen("php://output", "w");

if ($output === false) {
    http_response_code(500);
    exit("Could not create CSV file.");
}

fwrite($output, "\xEF\xBB\xBF");

fputcsv($output, [
    "Student ID",
    "Name",
    "Email",
    "Mobile",
    "Course",
    "Year",
    "Gender",
    "Status",
    "Registration Date"
]);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row["id"],
        $row["name"],
        $row["email"],
        $row["mobile"],
        $row["course"],
        $row["year"],
        $row["gender"],
        $row["status"],
        $row["created_at"]
    ]);
}

fclose($output);
writeAuditLog($conn, "CSV_EXPORT", "Exported student records to CSV.");
$stmt->close();
$conn->close();
exit;
?>
```