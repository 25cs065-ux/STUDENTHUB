```php
<?php
session_start();

if (isset($_SESSION["user_id"], $_SESSION["role"])) {
    if ($_SESSION["role"] === "Admin") {
        header("Location: admin_dashboard.php");
        exit;
    }

    if ($_SESSION["role"] === "Student") {
        header("Location: student_dashboard.php");
        exit;
    }
}

$error = "";

if (isset($_GET["error"])) {
    if ($_GET["error"] === "invalid") {
        $error = "Invalid email or password.";
    } elseif ($_GET["error"] === "empty") {
        $error = "Please enter both email and password.";
    } elseif ($_GET["error"] === "timeout") {
        $error = "Your session expired. Please log in again.";
    } elseif ($_GET["error"] === "unauthorized") {
        $error = "Unauthorized access. Please log in with the appropriate account.";
    } elseif ($_GET["error"] === "inactive") {
        $error = "This account has been deactivated. Please contact the administrator.";
    } elseif ($_GET["error"] === "locked") {
        $error = "Too many failed attempts. Please try again after 5 minutes.";
    } elseif ($_GET["error"] === "loggedout") {
        $error = "You have been logged out successfully.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f6fb;
            margin: 0;
            color: #243047;
        }

        .login-container {
            max-width: 420px;
            margin: 65px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 8px 28px rgba(30, 50, 80, 0.10);
        }

        .login-container h1 {
            text-align: center;
            margin-bottom: 10px;
        }

        .description {
            text-align: center;
            color: #667085;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
        }

        input[type="email"],
        input[type="password"] {
            box-sizing: border-box;
            width: 100%;
            padding: 12px;
            border: 1px solid #ccd3df;
            border-radius: 7px;
            font-size: 15px;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 7px;
            background: #2859b8;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #1e438e;
        }

        .error {
            background: #fff0f0;
            color: #a12626;
            border: 1px solid #f1c2c2;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 18px;
        }

        .success {
            background: #edf9f0;
            color: #216e39;
            border: 1px solid #b9e3c4;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 18px;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #2859b8;
        }
    </style>
</head>
<body>
    <main class="login-container">
        <h1>Student Hub</h1>
        <p class="description">Sign in to access your academic portal.</p>

        <?php if ($error !== ""): ?>
            <div class="error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET["registered"])): ?>
            <div class="success">
                Registration successful. You can now log in.
            </div>
        <?php endif; ?>

        <form action="authenticate.php" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your registered email"
                    autocomplete="username"
                    required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required>
            </div>

            <button type="submit">Login</button>
        </form>

        <div class="links">
            <p>Don't have an account? <a href="register.html">Register here</a></p>
            <p><a href="index.html">Back to Home</a></p>
        </div>
    </main>
</body>
</html>
```