<?php
require_once __DIR__ . '/db.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('index.php');
}

$notice = take_flash();
$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    try {
        $stmt = $conn->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            flash('Login successful. Welcome, ' . $admin['username'] . '.');
            redirect('index.php');
        }
        $error = 'Invalid username or password.';
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        $error = 'Login failed due to a database error. Please try again.';
    }
}
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login</title><link rel="stylesheet" href="style.css">
</head><body><main class="auth-card">
<h1>Administrator login</h1>
<?php if ($notice): ?><div class="notice <?= e($notice['type']) ?>"><?= e($notice['message']) ?></div><?php endif; ?>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label for="username">Username</label>
<input id="username" name="username" required autocomplete="username" value="<?= e($username) ?>">
<label for="password">Password</label>
<input id="password" name="password" type="password" required autocomplete="current-password">
<button class="primary full" type="submit">Log in</button>
</form>
<p><a href="register.php">Create the first admin account</a></p>
</main></body></html>
