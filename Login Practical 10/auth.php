
<?php
declare(strict_types=1);

const SESSION_TIMEOUT = 1200; // 20 minutes
const REMEMBER_COOKIE = 'charusat_remember';

$isHttps = isset($_SERVER['HTTPS'])
    && $_SERVER['HTTPS'] !== ''
    && strtolower($_SERVER['HTTPS']) !== 'off';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Expire an inactive logged-in session.
if (isset($_SESSION['user_id'])) {
    $lastActivity = (int)($_SESSION['last_activity'] ?? 0);

    if ($lastActivity === 0 ||
        time() - $lastActivity > SESSION_TIMEOUT) {
        $_SESSION = [];
        session_regenerate_id(true);
    } else {
        $_SESSION['last_activity'] = time();
    }
}

// Restore a session only if the remember token is valid.
if (!isset($_SESSION['user_id']) &&
    !empty($_COOKIE[REMEMBER_COOKIE])) {

    $rawToken = $_COOKIE[REMEMBER_COOKIE];

    // Tokens generated below are 64 hexadecimal characters.
    if (preg_match('/\A[a-f0-9]{64}\z/', $rawToken)) {
        require_once __DIR__ . '/db.php';

        $tokenHash = hash('sha256', $rawToken);

        $stmt = $conn->prepare(
            "SELECT id, name, email, role
             FROM users
             WHERE remember_token_hash = ?
               AND remember_token_expires > NOW()
             LIMIT 1"
        );

        $stmt->bind_param('s', $tokenHash);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            // Rotate the token to reduce replay risk.
            $newToken = bin2hex(random_bytes(32));
            $newHash = hash('sha256', $newToken);

            $update = $conn->prepare(
                "UPDATE users
                 SET remember_token_hash = ?,
                     remember_token_expires =
                         DATE_ADD(NOW(), INTERVAL 30 DAY)
                 WHERE id = ?"
            );

            $userId = (int)$user['id'];
            $update->bind_param('si', $newHash, $userId);
            $update->execute();
            $update->close();

            setcookie(REMEMBER_COOKIE, $newToken, [
                'expires' => time() + 2592000,
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        } else {
            setcookie(REMEMBER_COOKIE, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }
}

function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: Login.html');
        exit;
    }
}

function requireRole(string $role): void
{
    requireLogin();

    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }
}
