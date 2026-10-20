<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo 'Access denied. Administrator access is required. <a href="dashboard.php">Go to dashboard</a>';
    exit;
}
$notice = take_flash();
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Home — CHARUSAT</title><link rel="stylesheet" href="portal.css"><link rel="stylesheet" href="darktheme.css"></head>
<body><main class="container"><header class="topbar"><div><h1>CHARUSAT Admin Portal</h1><p>Welcome, <?=e($_SESSION['name'] ?? 'Administrator')?>. Choose a management area.</p></div><div class="actions"><button id="themeBtn" type="button" class="secondary">Dark Mode</button><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><button class="danger" type="submit">Log out</button></form></div></header>
<?php if ($notice): ?><div class="notice <?=e($notice['type'])?>" role="status"><?=e($notice['message'])?></div><?php endif; ?>
<div class="admin-links"><a class="admin-link" href="students.php"><h2>Student Management</h2><p>View, search, add, edit, and delete student records.</p><span>Open student management →</span></a><a class="admin-link" href="events.php"><h2>Event Management</h2><p>Create and manage campus events, event details, and poster uploads.</p><span>Open event management →</span></a></div>
</main><script src="darktheme.js"></script></body></html>
