<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Administrator access is required.');
}

function student_input(): array {
    return [
        'roll_no' => trim((string)($_POST['roll_no'] ?? '')),
        'full_name' => trim((string)($_POST['full_name'] ?? '')),
        'email' => strtolower(trim((string)($_POST['email'] ?? ''))),
        'mobile' => trim((string)($_POST['mobile'] ?? '')),
        'course' => (string)($_POST['course'] ?? ''),
        'year' => (string)($_POST['year'] ?? ''),
        'gender' => (string)($_POST['gender'] ?? '')
    ];
}
function student_validation(array $d): ?string {
    foreach (['roll_no','full_name','email','course','year','gender'] as $k) {
        if ($d[$k] === '') return 'Please complete all required fields.';
    }
    if (strlen($d['roll_no']) > 30 || strlen($d['full_name']) > 100) return 'Roll number or name is too long.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL) || strlen($d['email']) > 254) return 'Enter a valid email address.';
    if ($d['mobile'] !== '' && !preg_match('/\A[6-9][0-9]{9}\z/', $d['mobile'])) return 'Mobile must be a valid 10-digit Indian mobile number.';
    if (!in_array($d['course'], ['BCA','BBA','B.Tech','MCA','MBA'], true)) return 'Select a valid course.';
    if (!in_array($d['year'], ['1','2','3','4'], true)) return 'Select a valid year.';
    if (!in_array($d['gender'], ['Male','Female','Other'], true)) return 'Select a valid gender.';
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'add' || $action === 'update') {
            $d = student_input();
            $error = student_validation($d);
            if ($error !== null) throw new DomainException($error);
            $year = (int)$d['year'];
            if ($action === 'add') {
                $stmt = $conn->prepare('INSERT INTO students (roll_no,full_name,email,mobile,course,year,gender) VALUES (?,?,?,?,?,?,?)');
                $stmt->bind_param('sssssis', $d['roll_no'],$d['full_name'],$d['email'],$d['mobile'],$d['course'],$year,$d['gender']);
                $stmt->execute(); $stmt->close();
                flash('Student record added successfully.');
            } else {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                if (!$id) throw new DomainException('Invalid student record ID.');
                $stmt = $conn->prepare('UPDATE students SET roll_no=?,full_name=?,email=?,mobile=?,course=?,year=?,gender=? WHERE id=?');
                $stmt->bind_param('sssssisi', $d['roll_no'],$d['full_name'],$d['email'],$d['mobile'],$d['course'],$year,$d['gender'],$id);
                $stmt->execute(); $changed=$stmt->affected_rows; $stmt->close();
                if ($changed > 0) flash('Student record updated successfully.');
                else {
                    $check=$conn->prepare('SELECT id FROM students WHERE id=?');
                    $check->bind_param('i',$id); $check->execute(); $check->store_result();
                    $exists=$check->num_rows > 0; $check->close();
                    if (!$exists) throw new DomainException('Student record not found.');
                    flash('No changes were made; the record is unchanged.');
                }
            }
            redirect('students.php');
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            if (!$id) throw new DomainException('Invalid student record ID.');
            $stmt=$conn->prepare('DELETE FROM students WHERE id=?');
            $stmt->bind_param('i',$id); $stmt->execute(); $deleted=$stmt->affected_rows; $stmt->close();
            flash($deleted===1 ? 'Student record deleted successfully.' : 'Student record was not found.', $deleted===1?'success':'error');
            redirect('students.php');
        } else {
            throw new DomainException('Invalid action.');
        }
    } catch (DomainException $e) {
        flash($e->getMessage(),'error');
    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());
        flash((int)$e->getCode()===1062 ? 'That roll number is already in use.' : 'Database operation failed. Please check the database setup and try again.','error');
    } catch (Throwable $e) {
        error_log($e->getMessage());
        flash('Unexpected error. Please try again.','error');
    }
    redirect('students.php');
}

$notice=take_flash();
$empty=['id'=>'','roll_no'=>'','full_name'=>'','email'=>'','mobile'=>'','course'=>'','year'=>'','gender'=>''];
$form=$empty; $editing=false;
$editId=filter_input(INPUT_GET,'edit',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if ($editId) {
    $stmt=$conn->prepare('SELECT id,roll_no,full_name,email,mobile,course,year,gender FROM students WHERE id=?');
    $stmt->bind_param('i',$editId); $stmt->execute();
    $form=$stmt->get_result()->fetch_assoc() ?: $empty; $stmt->close();
    $editing=$form['id']!=='';
    if (!$editing) $notice=['message'=>'Student record not found.','type'=>'error'];
}
$search=trim((string)($_GET['search']??''));
$courseFilter=(string)($_GET['course']??'');
$yearFilter=(string)($_GET['year']??'');
$genderFilter=(string)($_GET['gender']??'');
if (!in_array($courseFilter,['','BCA','BBA','B.Tech','MCA','MBA'],true)) $courseFilter='';
if (!in_array($yearFilter,['','1','2','3','4'],true)) $yearFilter='';
if (!in_array($genderFilter,['','Male','Female','Other'],true)) $genderFilter='';

$term='%'.$search.'%';
$sql="SELECT id,roll_no,full_name,email,mobile,course,year,gender,created_at FROM students
 WHERE (roll_no LIKE ? OR full_name LIKE ? OR email LIKE ?)
 AND (?='' OR course=?) AND (?='' OR year=?) AND (?='' OR gender=?)
 ORDER BY id DESC";
$stmt=$conn->prepare($sql);
$stmt->bind_param('sssssssss',$term,$term,$term,$courseFilter,$courseFilter,$yearFilter,$yearFilter,$genderFilter,$genderFilter);
$stmt->execute(); $records=$stmt->get_result();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Student Records — CHARUSAT</title><link rel="stylesheet" href="portal.css"><link rel="stylesheet" href="darktheme.css"></head>
<body><main class="container">
<header class="topbar"><div><h1>Student Management</h1><p>Administrator: <?=e($_SESSION['name']??'Admin')?></p></div><div class="actions"><a class="btn secondary" href="admin.php">Admin home</a><a class="btn secondary" href="events.php">Event management</a><button id="themeBtn" type="button" class="secondary">Dark Mode</button><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><button class="danger" type="submit">Log out</button></form></div></header>
<?php if($notice):?><div class="notice <?=e($notice['type'])?>" role="status"><?=e($notice['message'])?></div><?php endif;?>
<section class="panel"><h2><?=$editing?'Edit student':'Add student'?></h2>
<form method="post" action="students.php"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="<?=$editing?'update':'add'?>">
<?php if($editing):?><input type="hidden" name="id" value="<?=e($form['id'])?>"><?php endif;?>
<div class="form-grid">
<div><label for="roll_no">Roll number *</label><input id="roll_no" name="roll_no" maxlength="30" required value="<?=e($form['roll_no'])?>"></div>
<div><label for="full_name">Student name *</label><input id="full_name" name="full_name" maxlength="100" required value="<?=e($form['full_name'])?>"></div>
<div><label for="email">Email *</label><input id="email" name="email" type="email" maxlength="254" required value="<?=e($form['email'])?>"></div>
<div><label for="mobile">Mobile</label><input id="mobile" name="mobile" maxlength="10" value="<?=e($form['mobile'])?>"></div>
<div><label for="course">Course *</label><select id="course" name="course" required><option value="">Select course</option><?php foreach(['BCA','BBA','B.Tech','MCA','MBA'] as $c):?><option value="<?=e($c)?>" <?=$form['course']===$c?'selected':''?>><?=e($c)?></option><?php endforeach;?></select></div>
<div><label for="year">Year *</label><select id="year" name="year" required><option value="">Select year</option><?php foreach(['1','2','3','4'] as $y):?><option value="<?=$y?>" <?=(string)$form['year']===$y?'selected':''?>>Year <?=$y?></option><?php endforeach;?></select></div>
<div><label for="gender">Gender *</label><select id="gender" name="gender" required><option value="">Select gender</option><?php foreach(['Male','Female','Other'] as $g):?><option value="<?=e($g)?>" <?=$form['gender']===$g?'selected':''?>><?=e($g)?></option><?php endforeach;?></select></div>
</div><div class="actions"><button class="primary" type="submit"><?=$editing?'Save changes':'Add student'?></button><?php if($editing):?><a class="btn secondary" href="students.php">Cancel</a><?php endif;?></div>
</form></section>
<section class="panel"><h2>Search and filter records</h2><form method="get" class="filters">
<div><label for="search">Search</label><input id="search" name="search" value="<?=e($search)?>" placeholder="Roll number, name, or email"></div>
<div><label for="course-filter">Course</label><select id="course-filter" name="course"><option value="">All courses</option><?php foreach(['BCA','BBA','B.Tech','MCA','MBA'] as $c):?><option value="<?=e($c)?>" <?=$courseFilter===$c?'selected':''?>><?=e($c)?></option><?php endforeach;?></select></div>
<div><label for="year-filter">Year</label><select id="year-filter" name="year"><option value="">All years</option><?php foreach(['1','2','3','4'] as $y):?><option value="<?=$y?>" <?=$yearFilter===$y?'selected':''?>>Year <?=$y?></option><?php endforeach;?></select></div>
<div><label for="gender-filter">Gender</label><select id="gender-filter" name="gender"><option value="">All genders</option><?php foreach(['Male','Female','Other'] as $g):?><option value="<?=e($g)?>" <?=$genderFilter===$g?'selected':''?>><?=e($g)?></option><?php endforeach;?></select></div>
<button class="primary" type="submit">Apply filters</button><a class="btn secondary" href="students.php">Reset</a>
</form></section>
<section class="panel"><h2>Student records</h2><p class="muted">Matching records: <?=$records->num_rows?></p><div class="table-wrap"><table>
<thead><tr><th>ID</th><th>Roll no.</th><th>Name</th><th>Email</th><th>Mobile</th><th>Course</th><th>Year</th><th>Gender</th><th>Actions</th></tr></thead><tbody>
<?php if($records->num_rows===0):?><tr><td colspan="9">No matching student records found.</td></tr><?php else:while($s=$records->fetch_assoc()):?><tr>
<td><?=e($s['id'])?></td><td><?=e($s['roll_no'])?></td><td><?=e($s['full_name'])?></td><td><?=e($s['email'])?></td><td><?=e($s['mobile'])?></td><td><?=e($s['course'])?></td><td><?=e($s['year'])?></td><td><?=e($s['gender'])?></td><td><div class="actions"><a class="btn secondary" href="students.php?edit=<?=e($s['id'])?>">Edit</a>
<form method="post" action="students.php" onsubmit="return confirm('Delete this student record permanently?')"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=e($s['id'])?>"><button class="danger" type="submit">Delete</button></form></div></td>
</tr><?php endwhile;endif;?>
</tbody></table></div></section>
</main><script src="darktheme.js"></script></body></html>
