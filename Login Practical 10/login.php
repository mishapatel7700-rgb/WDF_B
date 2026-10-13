<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Login.html');
    exit;
}

$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$remember = ($_POST['remember'] ?? '') === '1';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !is_string($password) ||
    $password === '' ||
    strlen($password) > 1024) {
    http_response_code(400);
    exit('Invalid email or password.');
}

$stmt = $conn->prepare(
    "SELECT id, name, email, password, role
     FROM users
     WHERE email = ?
     LIMIT 1"
);

$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    exit('Invalid email or password. <a href="Login.html">Try again</a>');
}

session_regenerate_id(true);

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['name'] = $user['name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['last_activity'] = time();

$isHttps = isset($_SERVER['HTTPS'])
    && $_SERVER['HTTPS'] !== ''
    && strtolower($_SERVER['HTTPS']) !== 'off';

if ($remember) {
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    $update = $conn->prepare(
        "UPDATE users
         SET remember_token_hash = ?,
             remember_token_expires =
                 DATE_ADD(NOW(), INTERVAL 30 DAY)
         WHERE id = ?"
    );

    $userId = (int)$user['id'];
    $update->bind_param('si', $tokenHash, $userId);
    $update->execute();
    $update->close();

    setcookie(REMEMBER_COOKIE, $token, [
        'expires' => time() + 2592000,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
} else {

    $update = $conn->prepare(
        "UPDATE users
         SET remember_token_hash = NULL,
             remember_token_expires = NULL
         WHERE id = ?"
    );

    $userId = (int)$user['id'];
    $update->bind_param('i', $userId);
    $update->execute();
    $update->close();

    setcookie(REMEMBER_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

if ($user['role'] === 'admin') {
    header('Location: admin.php');
} else {
    header('Location: dashboard.php');
}
exit;
