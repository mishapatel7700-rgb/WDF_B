<?php
require_once __DIR__ . '/auth.php';

function validate_student(array $d): ?string {
    foreach (['roll_no','full_name','email','gender','course'] as $key) {
        if ($d[$key] === '') return 'Please fill in all required fields.';
    }
    if (strlen($d['roll_no']) > 30 || strlen($d['full_name']) > 100 ||
        strlen($d['email']) > 150 || strlen($d['course']) > 100 ||
        strlen($d['phone']) > 20) {
        return 'One or more fields exceed the allowed length.';
    }
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) return 'Enter a valid email address.';
    if (!in_array($d['gender'], ['Male','Female','Other'], true)) return 'Select a valid gender.';
    return null;
}

function posted_student(): array {
    return [
        'roll_no' => trim((string)($_POST['roll_no'] ?? '')),
        'full_name' => trim((string)($_POST['full_name'] ?? '')),
        'email' => trim((string)($_POST['email'] ?? '')),
        'gender' => (string)($_POST['gender'] ?? ''),
        'course' => trim((string)($_POST['course'] ?? '')),
        'phone' => trim((string)($_POST['phone'] ?? ''))
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'add' || $action === 'update') {
            $d = posted_student();
            $validation = validate_student($d);
            if ($validation !== null) throw new DomainException($validation);

            if ($action === 'add') {
                $stmt = $conn->prepare('INSERT INTO students (roll_no,full_name,email,gender,course,phone) VALUES (?,?,?,?,?,?)');
                $stmt->bind_param('ssssss', $d['roll_no'],$d['full_name'],$d['email'],$d['gender'],$d['course'],$d['phone']);
                $stmt->execute();
                $stmt->close();
                flash('Student added successfully.');
            } else {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                if (!$id) throw new DomainException('Invalid student ID.');
                $stmt = $conn->prepare('UPDATE students SET roll_no=?,full_name=?,email=?,gender=?,course=?,phone=? WHERE id=?');
                $stmt->bind_param('ssssssi', $d['roll_no'],$d['full_name'],$d['email'],$d['gender'],$d['course'],$d['phone'],$id);
                $stmt->execute();
                $changed = $stmt->affected_rows;
                $stmt->close();

                if ($changed > 0) {
                    flash('Student updated successfully.');
                } else {
                    $check = $conn->prepare('SELECT id FROM students WHERE id=?');
                    $check->bind_param('i', $id);
                    $check->execute();
                    $check->store_result();
                    $exists = $check->num_rows > 0;
                    $check->close();
                    if (!$exists) throw new DomainException('Student not found. No changes were saved.');
                    flash('Student details are unchanged.');
                }
            }
            redirect('index.php');
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            if (!$id) throw new DomainException('Invalid student ID.');
            $stmt = $conn->prepare('DELETE FROM students WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();
            flash($deleted === 1 ? 'Student deleted successfully.' : 'Student not found. Nothing was deleted.', $deleted === 1 ? 'success' : 'error');
            redirect('index.php');
        } else {
            throw new DomainException('Invalid action requested.');
        }
    } catch (DomainException $ex) {
        flash($ex->getMessage(), 'error');
    } catch (mysqli_sql_exception $ex) {
        error_log($ex->getMessage());
        flash((int)$ex->getCode() === 1062 ? 'That roll number already exists.' : 'Database operation failed. Please try again.', 'error');
    } catch (Throwable $ex) {
        error_log($ex->getMessage());
        flash('An unexpected error occurred. Please try again.', 'error');
    }
    redirect('index.php');
}

$notice = take_flash();
$empty = ['id'=>'','roll_no'=>'','full_name'=>'','email'=>'','gender'=>'','course'=>'','phone'=>''];
$form = $empty;
$isEditing = false;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);

if ($editId) {
    try {
        $stmt = $conn->prepare('SELECT id,roll_no,full_name,email,gender,course,phone FROM students WHERE id=?');
        $stmt->bind_param('i', $editId);
        $stmt->execute();
        $form = $stmt->get_result()->fetch_assoc() ?: $empty;
        $stmt->close();
        $isEditing = $form['id'] !== '';
        if (!$isEditing) $notice = ['message'=>'Student not found.', 'type'=>'error'];
    } catch (mysqli_sql_exception $ex) {
        error_log($ex->getMessage());
        $notice = ['message'=>'Unable to load student details.', 'type'=>'error'];
    }
}

$search = trim((string)($_GET['search'] ?? ''));
$genderFilter = (string)($_GET['gender'] ?? '');
$courseFilter = trim((string)($_GET['course'] ?? ''));
if (!in_array($genderFilter, ['', 'Male', 'Female', 'Other'], true)) $genderFilter = '';

try {
    $term = '%' . $search . '%';
    $sql = "SELECT id,roll_no,full_name,email,gender,course,phone,created_at
            FROM students
            WHERE (roll_no LIKE ? OR full_name LIKE ? OR email LIKE ?)
            AND (?='' OR gender=?)
            AND (?='' OR course=?)
            ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssss', $term,$term,$term,$genderFilter,$genderFilter,$courseFilter,$courseFilter);
    $stmt->execute();
    $students = $stmt->get_result();

    $courseStmt = $conn->prepare("SELECT DISTINCT course FROM students WHERE course<>'' ORDER BY course");
    $courseStmt->execute();
    $courses = $courseStmt->get_result();
} catch (mysqli_sql_exception $ex) {
    error_log($ex->getMessage());
    http_response_code(500);
    exit('Unable to load student records. Check the database and try again.');
}
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student Management</title><link rel="stylesheet" href="style.css">
</head><body><main class="container">
<header class="topbar">
<div><h1>Student Management</h1><p class="muted">Signed in as <?= e($_SESSION['admin_username'] ?? 'Administrator') ?></p></div>
<form method="post" action="logout.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<button class="secondary" type="submit">Log out</button>
</form>
</header>

<?php if ($notice): ?><div class="notice <?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div><?php endif; ?>

<section class="panel">
<h2><?= $isEditing ? 'Edit student' : 'Add student' ?></h2>
<form method="post" action="index.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="<?= $isEditing ? 'update' : 'add' ?>">
<?php if ($isEditing): ?><input type="hidden" name="id" value="<?= e($form['id']) ?>"><?php endif; ?>
<div class="form-grid">
<div><label for="roll_no">Roll number *</label><input id="roll_no" name="roll_no" maxlength="30" required value="<?= e($form['roll_no']) ?>"></div>
<div><label for="full_name">Full name *</label><input id="full_name" name="full_name" maxlength="100" required value="<?= e($form['full_name']) ?>"></div>
<div><label for="email">Email *</label><input id="email" name="email" type="email" maxlength="150" required value="<?= e($form['email']) ?>"></div>
<div><label for="gender">Gender *</label><select id="gender" name="gender" required>
<option value="">Select gender</option>
<?php foreach (['Male','Female','Other'] as $g): ?><option value="<?= e($g) ?>" <?= $form['gender']===$g?'selected':'' ?>><?= e($g) ?></option><?php endforeach; ?>
</select></div>
<div><label for="course">Course *</label><input id="course" name="course" maxlength="100" required placeholder="e.g. BCA, BSc, MCA" value="<?= e($form['course']) ?>"></div>
<div><label for="phone">Phone</label><input id="phone" name="phone" maxlength="20" value="<?= e($form['phone']) ?>"></div>
</div>
<div class="actions" style="margin-top:18px">
<button class="primary" type="submit"><?= $isEditing ? 'Update student' : 'Add student' ?></button>
<?php if ($isEditing): ?><a class="btn secondary" href="index.php">Cancel edit</a><?php endif; ?>
</div><small>* Required fields</small>
</form>
</section>

<section class="panel">
<h2>Search and filter</h2>
<form method="get" action="index.php" class="filters">
<div><label for="search">Search</label><input id="search" name="search" value="<?= e($search) ?>" placeholder="Name, email or roll number"></div>
<div><label for="gender-filter">Gender</label><select id="gender-filter" name="gender"><option value="">All genders</option>
<?php foreach (['Male','Female','Other'] as $g): ?><option value="<?= e($g) ?>" <?= $genderFilter===$g?'selected':'' ?>><?= e($g) ?></option><?php endforeach; ?>
</select></div>
<div><label for="course-filter">Course</label><select id="course-filter" name="course"><option value="">All courses</option>
<?php while ($c = $courses->fetch_assoc()): ?><option value="<?= e($c['course']) ?>" <?= $courseFilter===$c['course']?'selected':'' ?>><?= e($c['course']) ?></option><?php endwhile; ?>
</select></div>
<button class="primary" type="submit">Apply</button><a class="btn secondary" href="index.php">Reset</a>
</form>
</section>

<section class="panel">
<h2>Student records</h2>
<p class="muted">Matching records: <?= $students->num_rows ?></p>
<div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Roll no.</th><th>Name</th><th>Email</th><th>Gender</th><th>Course</th><th>Phone</th><th>Actions</th></tr></thead>
<tbody>
<?php if ($students->num_rows === 0): ?><tr><td colspan="8">No student records found.</td></tr>
<?php else: while ($s = $students->fetch_assoc()): ?>
<tr>
<td><?= e($s['id']) ?></td><td><?= e($s['roll_no']) ?></td><td><?= e($s['full_name']) ?></td>
<td><?= e($s['email']) ?></td><td><?= e($s['gender']) ?></td><td><?= e($s['course']) ?></td><td><?= e($s['phone'] ?? '') ?></td>
<td><div class="actions">
<a class="btn secondary" href="index.php?edit=<?= e($s['id']) ?>">Edit</a>
<form class="inline" method="post" action="index.php" onsubmit="return confirm('Delete this student record? This cannot be undone.');">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e($s['id']) ?>">
<button class="danger" type="submit">Delete</button>
</form></div></td>
</tr>
<?php endwhile; endif; ?>
</tbody></table></div>
</section>
</main></body></html>
