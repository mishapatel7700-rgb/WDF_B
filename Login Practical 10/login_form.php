<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>

    <link rel="stylesheet" href="Login.css">
    <link rel="stylesheet" href="darktheme.css">
</head>
<body>

    <main class="login-container">
        <h1>Student Portal</h1>
        <h2>Login</h2>

        <form action="login.php" method="POST" id="loginForm">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <div class="form-group">
                <label for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <div class="remember-me">
                <input
                    type="checkbox"
                    id="remember"
                    name="remember"
                    value="1"
                >
                <label for="remember">Remember me for 30 days</label>
            </div>

            <button type="submit">Login</button>

            <p>
                Don't have an account?
                <a href="Register.html">Register here</a>
            </p>

        </form>
    </main>

    <script src="login.js"></script>
    <script src="darktheme.js"></script>

</body>
</html>
