<?php
require_once __DIR__ . '/auth.php';
if (($_SESSION['role'] ?? '') === 'admin') redirect('admin.php');
$notice = take_flash();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Student Dashboard</title><link rel="stylesheet" href="Login.css"></head>
<body><main class="auth-card"><h1>CHARUSAT Student Portal</h1>
<?php if ($notice): ?><div class="notice <?= e($notice['type']) ?>"><?= e($notice['message']) ?></div><?php endif; ?>
<h2>Welcome, <?= e($_SESSION['name'] ?? 'Student') ?></h2>
<p>You are logged in as a student. Student record management is restricted to administrators.</p>
<form action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="main-button" type="submit">Log out</button></form>
</main></body></html>
