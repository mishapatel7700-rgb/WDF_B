<?php
require_once __DIR__ . '/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
verify_csrf();
if (!empty($_SESSION['user_id'])) {
    $id = (int)$_SESSION['user_id'];
    try {
        $stmt = $conn->prepare('UPDATE users SET remember_token_hash=NULL, remember_token_expires=NULL WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    } catch (mysqli_sql_exception $e) { error_log($e->getMessage()); }
}
$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && strtolower((string)$_SERVER['HTTPS']) !== 'off';
setcookie('charusat_remember', '', ['expires'=>time()-3600,'path'=>'/','secure'=>$isHttps,'httponly'=>true,'samesite'=>'Lax']);
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', ['expires'=>time()-42000,'path'=>$p['path'],'domain'=>$p['domain'],'secure'=>$p['secure'],'httponly'=>$p['httponly'],'samesite'=>'Lax']);
}
session_destroy();
header('Location: Login.php');
exit;
