<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' &&
        strtolower((string)$_SERVER['HTTPS']) !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => '/'
    ]);
    session_start();
}

try {
    // Change these credentials if your MySQL setup is different.
    $conn = new mysqli('localhost', 'root', '', 'charusat_portal');
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check db.php settings and ensure MySQL is running.');
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $posted = $_POST['csrf_token'] ?? '';
    if (!is_string($posted) || !hash_equals(csrf_token(), $posted)) {
        http_response_code(403);
        exit('Request rejected because the security token is invalid. Return to the page and try again.');
    }
}

function flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array {
    $notice = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($notice) ? $notice : null;
}

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}
