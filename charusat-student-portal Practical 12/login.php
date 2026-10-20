<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('Login.php');
}
verify_csrf();

$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = $_POST['password'] ?? '';
$remember = ($_POST['remember'] ?? '') === '1';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !is_string($password) ||
    $password === '' || strlen($password) > 1024) {
    flash('Please enter a valid email and password.', 'error');
    redirect('Login.php');
}

try {
    $stmt = $conn->prepare('SELECT id,name,email,password,role FROM users WHERE email=? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['password'])) {
        flash('Invalid email or password.', 'error');
        redirect('Login.php');
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_activity'] = time();

    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' &&
        strtolower((string)$_SERVER['HTTPS']) !== 'off';

    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $id = (int)$user['id'];
        $update = $conn->prepare('UPDATE users SET remember_token_hash=?, remember_token_expires=DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id=?');
        $update->bind_param('si', $tokenHash, $id);
        $update->execute();
        $update->close();
        setcookie('charusat_remember', $token, [
            'expires' => time() + 2592000, 'path' => '/', 'secure' => $isHttps,
            'httponly' => true, 'samesite' => 'Lax'
        ]);
    } else {
        $id = (int)$user['id'];
        $update = $conn->prepare('UPDATE users SET remember_token_hash=NULL, remember_token_expires=NULL WHERE id=?');
        $update->bind_param('i', $id);
        $update->execute();
        $update->close();
        setcookie('charusat_remember', '', [
            'expires' => time() - 3600, 'path' => '/', 'secure' => $isHttps,
            'httponly' => true, 'samesite' => 'Lax'
        ]);
    }

    flash('Login successful. Welcome, ' . $user['name'] . '.');
    redirect($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php');
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    flash('Login could not be completed due to a database error.', 'error');
    redirect('Login.php');
}
