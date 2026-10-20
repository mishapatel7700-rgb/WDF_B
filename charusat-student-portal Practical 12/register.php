<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

function registration_message(string $title, string $message, int $status = 400): never {
    http_response_code($status);
    $safeTitle = e($title);
    $safeMessage = e($message);
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' .
        $safeTitle . '</title><link rel="stylesheet" href="Register.css"></head><body><main class="auth-card"><h2>' .
        $safeTitle . '</h2><p>' . $safeMessage . '</p><p><a href="Register.html">Back to registration</a> · <a href="Login.php">Login</a></p></main></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('Register.html');

$name = trim((string)($_POST['name'] ?? ''));
$username = strtolower(trim((string)($_POST['username'] ?? '')));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$mobile = trim((string)($_POST['mobile'] ?? ''));
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$course = (string)($_POST['course'] ?? '');
$year = (string)($_POST['year'] ?? '');
$gender = (string)($_POST['gender'] ?? '');
$terms = (string)($_POST['terms'] ?? '');

if (!preg_match('/\A[A-Za-z ]{2,50}\z/', $name)) registration_message('Invalid name', 'Enter a name containing 2–50 letters and spaces.');
if (!preg_match('/\A[A-Za-z0-9_]{3,50}\z/', $username)) registration_message('Invalid username', 'Use 3–50 letters, numbers or underscores.');
if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) registration_message('Invalid email', 'Please enter a valid email address.');
if (!preg_match('/\A[6-9][0-9]{9}\z/', $mobile)) registration_message('Invalid mobile', 'Enter a valid 10-digit Indian mobile number.');

$passwordRegex = '/\A(?=.*[A-Za-z])(?=.*[0-9])(?=.*[@$!%*?&])[A-Za-z0-9@$!%*?&]{8,72}\z/';
if (!is_string($password) || !preg_match($passwordRegex, $password)) {
    registration_message('Invalid password', 'Password must be 8–72 characters with a letter, number and special character.');
}
if (!is_string($confirmPassword) || !hash_equals($password, $confirmPassword)) registration_message('Password error', 'Passwords do not match.');
if (!in_array($course, ['BCA','BBA','B.Tech','MCA','MBA'], true)) registration_message('Invalid course', 'Please select a valid course.');
if (!in_array($year, ['1','2','3','4'], true)) registration_message('Invalid year', 'Please select a valid year.');
if (!in_array($gender, ['Male','Female','Other'], true)) registration_message('Invalid gender', 'Please select a valid gender.');
if ($terms !== 'accepted') registration_message('Terms required', 'You must accept the Terms and Conditions.');

try {
    $check = $conn->prepare('SELECT username,email FROM users WHERE username=? OR email=?');
    $check->bind_param('ss', $username, $email);
    $check->execute();
    $result = $check->get_result();
    $usernameExists = false;
    $emailExists = false;
    while ($row = $result->fetch_assoc()) {
        if (strcasecmp($row['username'], $username) === 0) $usernameExists = true;
        if (strcasecmp($row['email'], $email) === 0) $emailExists = true;
    }
    $check->close();

    if ($usernameExists && $emailExists) registration_message('Registration failed', 'Both username and email are already registered.', 409);
    if ($usernameExists) registration_message('Registration failed', 'This username is already taken.', 409);
    if ($emailExists) registration_message('Registration failed', 'This email is already registered.', 409);

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $yearNumber = (int)$year;
    // role is deliberately omitted; database default makes public registrations students, not admins.
    $stmt = $conn->prepare('INSERT INTO users (name,username,email,mobile,password,course,year,gender) VALUES (?,?,?,?,?,?,?,?)');
    $stmt->bind_param('ssssssis', $name,$username,$email,$mobile,$passwordHash,$course,$yearNumber,$gender);
    $stmt->execute();
    $stmt->close();
    registration_message('Registration successful', 'Your CHARUSAT account has been created. You can now log in.', 201);
} catch (mysqli_sql_exception $e) {
    error_log('Registration database error: ' . $e->getMessage());
    if ((int)$e->getCode() === 1062) registration_message('Registration failed', 'This username or email is already registered.', 409);
    registration_message('Registration error', 'Registration is temporarily unavailable. Please try again later.', 500);
} catch (Throwable $e) {
    error_log('Registration error: ' . $e->getMessage());
    registration_message('Registration error', 'Unable to complete registration. Please try again later.', 500);
}
