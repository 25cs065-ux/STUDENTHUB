
<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!isset($_SESSION["user_id"])) {
    if (!isset($_SESSION["session_id_before_login"])) {
        $_SESSION["session_id_before_login"] = session_id();
    }

    $beforeLoginId = htmlspecialchars(
        $_SESSION["session_id_before_login"],
        ENT_QUOTES,
        "UTF-8"
    );
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Session ID Test</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <main class="container">
            <h1>Session ID Regeneration Test</h1>
            <p><strong>Status:</strong> Not logged in</p>
            <p><strong>Session ID before login:</strong></p>
            <p><?php echo $beforeLoginId; ?></p>
            <p>Now log in using your test account.</p>
            <p><a href="login.php">Go to Login</a></p>
        </main>
    </body>
    </html>
    <?php
    exit;
}

$beforeLoginId = $_SESSION["session_id_before_login"] ?? null;
$currentSessionId = session_id();

$regenerated = (
    $beforeLoginId !== null &&
    $beforeLoginId !== $currentSessionId
);

$safeBefore = htmlspecialchars(
    $beforeLoginId ?? "Not recorded",
    ENT_QUOTES,
    "UTF-8"
);

$safeCurrent = htmlspecialchars(
    $currentSessionId,
    ENT_QUOTES,
    "UTF-8"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session ID Test Result</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="container">
        <h1>Session ID Regeneration Test</h1>

        <p><strong>Login status:</strong> Logged in</p>

        <p><strong>Session ID before login:</strong></p>
        <p><?php echo $safeBefore; ?></p>

        <p><strong>Session ID after login:</strong></p>
        <p><?php echo $safeCurrent; ?></p>

        <h2>
            Result:
            <?php echo $regenerated ? "PASS" : "NOT VERIFIED"; ?>
        </h2>

        <p>
            <?php
            echo $regenerated
                ? "The session ID changed after login."
                : "The session ID change could not be verified. Log out and repeat the test.";
            ?>
        </p>

        <p><a href="logout.php">Logout</a></p>
    </main>
</body>
</html>
