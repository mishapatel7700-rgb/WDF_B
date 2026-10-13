<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare(
        "UPDATE users
         SET remember_token_hash = NULL,
             remember_token_expires = NULL
         WHERE id = ?"
    );

    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
}

$isHttps = isset($_SERVER['HTTPS'])
    && $_SERVER['HTTPS'] !== ''
    && strtolower($_SERVER['HTTPS']) !== 'off';

setcookie(REMEMBER_COOKIE, '', [
    'expires' => time() - 3600,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax'
    ]);
}

session_destroy();

header('Location: Login.html');
exit;
