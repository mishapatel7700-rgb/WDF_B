
<?php
require_once __DIR__ . '/auth.php';

requireLogin();

if (($_SESSION['role'] ?? '') !== 'student') {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: admin.php');
        exit;
    }

    http_response_code(403);
    exit('Access denied.');
}
?>
