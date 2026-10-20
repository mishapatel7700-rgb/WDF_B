<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

// Restore a remembered session when the browser presents the HTTP-only token.
if (empty($_SESSION['user_id']) && !empty($_COOKIE['charusat_remember']) &&
    is_string($_COOKIE['charusat_remember']) &&
    preg_match('/\\A[a-f0-9]{64}\\z/', $_COOKIE['charusat_remember'])) {
    try {
        $tokenHash = hash('sha256', $_COOKIE['charusat_remember']);
        $stmt = $conn->prepare(
            'SELECT id, name, email, role FROM users
             WHERE remember_token_hash = ?
               AND remember_token_expires > NOW()
             LIMIT 1'
        );
        $stmt->bind_param('s', $tokenHash);
        $stmt->execute();
        $remembered = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($remembered) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$remembered['id'];
            $_SESSION['name'] = $remembered['name'];
            $_SESSION['email'] = $remembered['email'];
            $_SESSION['role'] = $remembered['role'];
            $_SESSION['last_activity'] = time();
        }
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
    }
}

if (empty($_SESSION['user_id'])) {
    flash('Please log in to continue.', 'error');
    redirect('Login.php');
}

$lastActivity = (int)($_SESSION['last_activity'] ?? time());
if (time() - $lastActivity > 1800) {
    $_SESSION = [];
    session_regenerate_id(true);
    flash('Your session expired. Please log in again.', 'error');
    redirect('Login.html');
}
$_SESSION['last_activity'] = time();
