<?php
require_once __DIR__ . '/db.php';

if (empty($_SESSION['admin_id'])) {
    flash('Please log in to access student records.', 'error');
    redirect('login.php');
}
