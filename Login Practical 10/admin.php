
<?php
require_once __DIR__ . '/auth.php';
requireRole('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="Login.css">
</head>
<body>
    <h1>CHARUSAT Admin Dashboard</h1>
    <main>
        <p>Welcome, administrator.</p>

        <form action="logout.php" method="POST">
            <button type="submit">Logout</button>
        </form>
    </main>
</body>
</html>
