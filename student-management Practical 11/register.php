<?php
require_once __DIR__ . '/db.php';

$countResult = $conn->query('SELECT COUNT(*) AS total FROM admins');
$adminCount = (int)$countResult->fetch_assoc()['total'];

if ($adminCount > 0) {
    flash('An administrator account already exists. Please log in.', 'error');
    redirect('login.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
        $error = 'Username must be 3–50 characters and use letters, numbers, dot, underscore or hyphen.';
    } elseif (strlen($password) < 10) {
        $error = 'Password must contain at least 10 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Re-check inside the write path so registration closes after the first admin.
            $conn->begin_transaction();
            $check = $conn->query('SELECT id FROM admins LIMIT 1 FOR UPDATE');
            if ($check->num_rows > 0) {
                $conn->rollback();
                $error = 'An administrator account already exists. Please log in.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
                $stmt->bind_param('ss', $username, $hash);
                $stmt->execute();
                $stmt->close();
                $conn->commit();
                flash('Administrator account created. You can now log in.');
                redirect('login.php');
            }
        } catch (mysqli_sql_exception $e) {
            try { $conn->rollback(); } catch (Throwable $ignored) {}
            error_log($e->getMessage());
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Registration</title><link rel="stylesheet" href="style.css">
</head><body><main class="auth-card">
<h1>Create administrator</h1>
<p class="muted">Registration is available only until the first admin account is created.</p>
<?php if ($error): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<label for="username">Username</label>
<input id="username" name="username" required maxlength="50" autocomplete="username" value="<?= e($username) ?>">
<label for="password">Password (at least 10 characters)</label>
<input id="password" name="password" type="password" required minlength="10" autocomplete="new-password">
<label for="confirm_password">Confirm password</label>
<input id="confirm_password" name="confirm_password" type="password" required minlength="10" autocomplete="new-password">
<button class="primary full" type="submit">Create admin account</button>
</form>
<p><a href="login.php">Go to login</a></p>
</main></body></html>
