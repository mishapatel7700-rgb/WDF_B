<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied. Administrator access is required.');
}

const POSTER_MAX_BYTES = 5 * 1024 * 1024; // 5 MB
const POSTER_DIR = __DIR__ . '/uploads/event-posters';
const POSTER_WEB_DIR = 'uploads/event-posters';
const POSTER_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

function event_input(): array {
    return [
        'title' => trim((string)($_POST['title'] ?? '')),
        'description' => trim((string)($_POST['description'] ?? '')),
        'event_date' => trim((string)($_POST['event_date'] ?? '')),
        'location' => trim((string)($_POST['location'] ?? '')),
    ];
}
function validate_event(array $d): ?string {
    if ($d['title'] === '' || $d['description'] === '' || $d['event_date'] === '' || $d['location'] === '') {
        return 'Please complete all required event fields.';
    }
    if (mb_strlen($d['title']) > 150) return 'Event title must be 150 characters or fewer.';
    if (mb_strlen($d['location']) > 180) return 'Location must be 180 characters or fewer.';
    $date = DateTime::createFromFormat('Y-m-d\TH:i', $d['event_date']);
    if (!$date || $date->format('Y-m-d\TH:i') !== $d['event_date']) return 'Enter a valid event date and time.';
    return null;
}
/** Validate and store an uploaded image; returns its relative path or null when no file was selected. */
function save_poster(array $file): ?string {
    if (!isset($file['error']) || is_array($file['error'])) throw new DomainException('Invalid poster upload.');
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new DomainException('Poster is too large. Maximum allowed size is 5 MB.');
        }
        throw new DomainException('Poster upload failed. Please choose the file again.');
    }
    if (!is_uploaded_file($file['tmp_name'] ?? '')) throw new DomainException('Invalid uploaded file.');
    if ((int)($file['size'] ?? 0) < 1 || (int)$file['size'] > POSTER_MAX_BYTES) {
        throw new DomainException('Poster must be smaller than or equal to 5 MB.');
    }
    if (!class_exists('finfo')) throw new RuntimeException('PHP Fileinfo is required to validate poster uploads. Enable the Fileinfo extension.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset(POSTER_TYPES[$mime])) throw new DomainException('Unsupported poster format. Upload a JPG, PNG, or WEBP image.');
    $image = @getimagesize($file['tmp_name']);
    if ($image === false || ($image['mime'] ?? '') !== $mime) throw new DomainException('The selected file is not a valid image.');
    if (!is_dir(POSTER_DIR) && !mkdir(POSTER_DIR, 0755, true) && !is_dir(POSTER_DIR)) {
        throw new RuntimeException('Could not create the poster upload directory.');
    }
    $filename = bin2hex(random_bytes(20)) . '.' . POSTER_TYPES[$mime];
    if (!move_uploaded_file($file['tmp_name'], POSTER_DIR . '/' . $filename)) throw new RuntimeException('Could not save the uploaded poster. Check folder permissions.');
    return POSTER_WEB_DIR . '/' . $filename;
}
function remove_poster(?string $relativePath): void {
    if (!$relativePath || !str_starts_with($relativePath, POSTER_WEB_DIR . '/')) return;
    $filename = basename($relativePath);
    if ($filename !== '' && $filename !== '.' && $filename !== '..') {
        $path = POSTER_DIR . '/' . $filename;
        if (is_file($path)) @unlink($path);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $newPoster = null;
    try {
        if ($action === 'add' || $action === 'update') {
            $d = event_input();
            $error = validate_event($d);
            if ($error !== null) throw new DomainException($error);
            $d['event_date'] = str_replace('T', ' ', $d['event_date']) . ':00';
            $id = 0;
            $oldPoster = null;
            if ($action === 'update') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
                if (!$id) throw new DomainException('Invalid event ID.');
                $check = $conn->prepare('SELECT poster_path FROM events WHERE id=?');
                $check->bind_param('i', $id); $check->execute();
                $existing = $check->get_result()->fetch_assoc(); $check->close();
                if (!$existing) throw new DomainException('Event not found.');
                $oldPoster = $existing['poster_path'];
            }
            $newPoster = save_poster($_FILES['poster'] ?? ['error' => UPLOAD_ERR_NO_FILE]);
            if ($action === 'add') {
                $stmt = $conn->prepare('INSERT INTO events (title,description,event_date,location,poster_path,created_by) VALUES (?,?,?,?,?,?)');
                $adminId = (int)$_SESSION['user_id'];
                $stmt->bind_param('sssssi', $d['title'], $d['description'], $d['event_date'], $d['location'], $newPoster, $adminId);
                $stmt->execute(); $stmt->close();
                flash('Event added successfully.');
            } else {
                $posterPath = $newPoster ?? $oldPoster;
                $stmt = $conn->prepare('UPDATE events SET title=?,description=?,event_date=?,location=?,poster_path=? WHERE id=?');
                $stmt->bind_param('sssssi', $d['title'], $d['description'], $d['event_date'], $d['location'], $posterPath, $id);
                $stmt->execute(); $stmt->close();
                if ($newPoster !== null && $oldPoster !== $newPoster) remove_poster($oldPoster);
                flash('Event updated successfully.');
            }
            redirect('events.php');
        } elseif ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
            if (!$id) throw new DomainException('Invalid event ID.');
            $stmt = $conn->prepare('SELECT poster_path FROM events WHERE id=?');
            $stmt->bind_param('i', $id); $stmt->execute(); $event = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if (!$event) throw new DomainException('Event was not found.');
            $stmt = $conn->prepare('DELETE FROM events WHERE id=?');
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            remove_poster($event['poster_path'] ?? null);
            flash('Event deleted successfully.');
            redirect('events.php');
        } else {
            throw new DomainException('Invalid action.');
        }
    } catch (DomainException $e) {
        if ($newPoster !== null) remove_poster($newPoster);
        flash($e->getMessage(), 'error');
    } catch (mysqli_sql_exception $e) {
        if ($newPoster !== null) remove_poster($newPoster);
        error_log($e->getMessage());
        flash('Database operation failed. Please check that the events table exists, then try again.', 'error');
    } catch (Throwable $e) {
        if ($newPoster !== null) remove_poster($newPoster);
        error_log($e->getMessage());
        flash('An unexpected error occurred. Check the PHP error log and try again.', 'error');
    }
    redirect('events.php');
}

$notice = take_flash();
$empty = ['id'=>'','title'=>'','description'=>'','event_date'=>'','location'=>'','poster_path'=>null];
$form = $empty; $editing = false;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($editId) {
    $stmt = $conn->prepare('SELECT id,title,description,DATE_FORMAT(event_date, "%Y-%m-%dT%H:%i") AS event_date,location,poster_path FROM events WHERE id=?');
    $stmt->bind_param('i', $editId); $stmt->execute();
    $form = $stmt->get_result()->fetch_assoc() ?: $empty; $stmt->close();
    $editing = $form['id'] !== '';
    if (!$editing) $notice = ['message'=>'Event not found.', 'type'=>'error'];
}
$stmt = $conn->prepare('SELECT id,title,description,event_date,location,poster_path,created_at FROM events ORDER BY event_date DESC, id DESC');
$stmt->execute(); $events = $stmt->get_result();
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Event Management — CHARUSAT</title><link rel="stylesheet" href="portal.css"><link rel="stylesheet" href="darktheme.css"></head>
<body><main class="container">
<header class="topbar"><div><h1>Event Management</h1><p>Administrator: <?=e($_SESSION['name'] ?? 'Admin')?> · Manage events and posters</p></div><div class="actions"><a class="btn secondary" href="admin.php">Admin home</a><a class="btn secondary" href="students.php">Student management</a><button id="themeBtn" type="button" class="secondary">Dark Mode</button><form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><button class="danger" type="submit">Log out</button></form></div></header>
<?php if ($notice): ?><div class="notice <?=e($notice['type'])?>" role="status"><?=e($notice['message'])?></div><?php endif; ?>
<section class="panel"><h2><?=$editing ? 'Update event' : 'Add event'?></h2><p class="muted">Poster formats: JPG, PNG, or WEBP. Maximum file size: 5 MB.</p>
<form method="post" action="events.php" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="<?=$editing ? 'update' : 'add'?>"><?php if ($editing): ?><input type="hidden" name="id" value="<?=e($form['id'])?>"><?php endif; ?>
<div class="form-grid"><div><label for="title">Event title *</label><input id="title" name="title" maxlength="150" required value="<?=e($form['title'])?>"></div><div><label for="event_date">Date and time *</label><input id="event_date" name="event_date" type="datetime-local" required value="<?=e($form['event_date'])?>"></div><div><label for="location">Location *</label><input id="location" name="location" maxlength="180" required value="<?=e($form['location'])?>"></div>
<div class="full-width"><label for="description">Description *</label><textarea id="description" name="description" rows="4" required maxlength="10000"><?=e($form['description'])?></textarea></div>
<div class="full-width"><label for="poster">Event poster <?=$editing ? '(optional — choose a new image to replace the current poster)' : '(optional)'?></label><input id="poster" name="poster" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><small class="muted">JPG, PNG, WEBP · Up to 5 MB</small></div></div>
<?php if ($editing && !empty($form['poster_path'])): ?><p>Current poster:</p><img class="event-poster-preview" src="<?=e($form['poster_path'])?>" alt="Current poster for <?=e($form['title'])?>"><?php endif; ?>
<div class="actions"><button class="primary" type="submit"><?=$editing ? 'Save event changes' : 'Add event'?></button><?php if ($editing): ?><a class="btn secondary" href="events.php">Cancel</a><?php endif; ?></div></form></section>
<section class="panel"><h2>Events</h2><p class="muted">Total events: <?=$events->num_rows?></p><div class="event-grid">
<?php if ($events->num_rows === 0): ?><p>No events have been added yet.</p><?php else: while ($event = $events->fetch_assoc()): ?><article class="event-card">
<?php if (!empty($event['poster_path'])): ?><a href="<?=e($event['poster_path'])?>" target="_blank" rel="noopener"><img class="event-poster" src="<?=e($event['poster_path'])?>" alt="Poster for <?=e($event['title'])?>" loading="lazy"></a><?php else: ?><div class="poster-placeholder">No poster uploaded</div><?php endif; ?>
<div class="event-card-content"><h3><?=e($event['title'])?></h3><p><strong>Date:</strong> <?=e(date('M j, Y · g:i A', strtotime($event['event_date'])))?></p><p><strong>Location:</strong> <?=e($event['location'])?></p><p class="event-description"><?=nl2br(e($event['description']))?></p><div class="actions"><a class="btn secondary" href="events.php?edit=<?=e($event['id'])?>">Edit</a><form method="post" action="events.php" onsubmit="return confirm('Delete this event and its poster permanently?')"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=e($event['id'])?>"><button class="danger" type="submit">Delete</button></form></div></div></article><?php endwhile; endif; ?>
</div></section></main><script src="darktheme.js"></script></body></html>
