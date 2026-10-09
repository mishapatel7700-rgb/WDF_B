<?php

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$mobile = trim($_POST["mobile"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";
$course = trim($_POST["course"] ?? "");
$year = trim($_POST["year"] ?? "");
$gender = trim($_POST["gender"] ?? "");
$terms = $_POST["terms"] ?? "";

$errors = [];

if ($name === "") {
    $errors[] = "Name is required.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email address.";
}

if (!preg_match("/^[0-9]{10}$/", $mobile)) {
    $errors[] = "Mobile number must contain 10 digits.";
}

if (strlen($password) < 6) {
    $errors[] = "Password must be at least 6 characters.";
}

if ($password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
}

$validCourses = ["BCA", "BBA", "B.Tech", "MCA", "MBA"];

if (!in_array($course, $validCourses, true)) {
    $errors[] = "Invalid course.";
}

if (!in_array($year, ["1", "2", "3", "4"], true)) {
    $errors[] = "Invalid year.";
}

if (!in_array($gender, ["Male", "Female", "Other"], true)) {
    $errors[] = "Invalid gender.";
}

if ($terms !== "accepted") {
    $errors[] = "You must accept the terms and conditions.";
}

if (!empty($errors)) {
    echo "<h2>Registration Failed</h2>";

    foreach ($errors as $error) {
        echo "<p>" . htmlspecialchars($error) . "</p>";
    }

    echo '<a href="register.html">Go Back</a>';
    exit;
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$file = "registrations.csv";

if (!file_exists($file)) {
    $handle = fopen($file, "w");

    fputcsv($handle, [
        "Name",
        "Email",
        "Mobile",
        "Password",
        "Course",
        "Year",
        "Gender"
    ]);

    fclose($handle);
}

$handle = fopen($file, "a");

if ($handle === false) {
    die("Error: Unable to open registration file.");
}

fputcsv($handle, [
    $name,
    $email,
    $mobile,
    $passwordHash,
    $course,
    $year,
    $gender
]);

fclose($handle);

echo "<h2>Registration Successful!</h2>";
echo "<p>Welcome, " . htmlspecialchars($name) . ".</p>";
echo '<a href="register.html">Register another student</a>';

?>