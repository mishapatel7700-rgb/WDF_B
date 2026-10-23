<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "charusat_portal_db";

$conn = new mysqli($host, $username, $password);
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

$table_sql = "CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    course VARCHAR(50) NOT NULL
)";
$conn->query($table_sql);

$check_empty = $conn->query("SELECT COUNT(*) as count FROM members");
$row = $check_empty->fetch_assoc();
if ($row['count'] == 0) {
    $conn->query("INSERT INTO members (name, email, course) VALUES 
        ('Misha Patel', 'misha7700@gmail.com', 'IT'),
        ('Tisha Patel', 'tishapatel7700@gmail.com', 'CS')");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $course = trim($_POST['course']);

    if (!empty($name) && !empty($email) && !empty($course)) {
        $stmt = $conn->prepare("INSERT INTO members (name, email, course) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $course);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$course_filter = isset($_GET['course_filter']) ? trim($_GET['course_filter']) : '';

$query = "SELECT * FROM members WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
    $search_param = "%" . $search . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($course_filter) && $course_filter !== 'All Courses') {
    $query .= " AND course = ?";
    $params[] = $course_filter;
    $types .= "s";
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$courses_result = $conn->query("SELECT DISTINCT course FROM members");
$all_courses = [];
while ($c_row = $courses_result->fetch_assoc()) {
    $all_courses[] = $c_row['course'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charusat Portal - Club Member Management (CRUD)</title>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="header">
        <h1>Charusat Portal - Club Member Management (CRUD)</h1>
    </header>
    <main class="container">
        <section class="card">
            <h2>Add New Member</h2>
            <form action="index.php" method="POST" class="inline-form">
                <input type="text" name="name" placeholder="Full Name" required autocomplete="off">
                <input type="email" name="email" placeholder="Email Address" required autocomplete="off">
                <input type="text" name="course" placeholder="Course / Branch" required autocomplete="off">
                <button type="submit" name="add_member" class="btn-primary">Add Member</button>
            </form>
        </section>
        <section class="card filter-card">
            <form action="index.php" method="GET" class="inline-filter-form">
                <input type="text" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                <select name="course_filter">
                    <option value="All Courses">All Courses</option>
                    <?php foreach ($all_courses as $c): ?>
                        <option value="<?php echo htmlspecialchars($c); ?>" <?php if ($course_filter === $c) echo 'selected'; ?>><?php echo htmlspecialchars($c); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-filter">Filter / Search</button>
                <a href="index.php" class="btn-reset">Reset</a>
            </form>
        </section>
        <section class="card table-card">
            <h2>Members List</h2>
            <table class="members-table">
                <thead>
                    <tr>
                        <th style="width: 8%;">ID</th>
                        <th style="width: 22%;">Name</th>
                        <th style="width: 45%;">Email</th>
                        <th style="width: 15%;">Course</th>
                        <th style="width: 10%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['course']); ?></td>
                                <td>
                                    <form action="index.php" method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to delete this member?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="link-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #7f8c8d; padding: 20px;">No registered portal records correspond with specified lookup rules.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>