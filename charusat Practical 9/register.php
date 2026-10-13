
<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: Register.html");
    exit;
}

function showMessage($title, $message, $status = 400) {
    http_response_code($status);

    echo '<!DOCTYPE html><html lang="en"><head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . '</title>';
    echo '</head><body>';

    echo '<h2>' . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . '</h2>';
    echo '<p>' . htmlspecialchars($message, ENT_QUOTES, "UTF-8") . '</p>';
    echo '<p><a href="Register.html">Back to Registration</a></p>';
    echo '</body></html>';
    exit;
}

$name = trim($_POST["name"] ?? "");
$username = strtolower(trim($_POST["username"] ?? ""));
$email = strtolower(trim($_POST["email"] ?? ""));
$mobile = trim($_POST["mobile"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";
$course = $_POST["course"] ?? "";
$year = $_POST["year"] ?? "";
$gender = $_POST["gender"] ?? "";
$terms = $_POST["terms"] ?? "";

if (!preg_match('/\A[A-Za-z ]{2,50}\z/', $name)) {
    showMessage("Invalid Name", "Enter a name containing 2–50 letters and spaces.");
}

if (!preg_match('/\A[A-Za-z0-9_]{3,50}\z/', $username)) {
    showMessage("Invalid Username", "Use 3–50 letters, numbers, or underscores.");
}

if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    showMessage("Invalid Email", "Please enter a valid email address.");
}

if (!preg_match('/\A[6-9][0-9]{9}\z/', $mobile)) {
    showMessage("Invalid Mobile", "Enter a valid 10-digit Indian mobile number.");
}

$passwordRegex =
    '/\A(?=.*[A-Za-z])(?=.*[0-9])(?=.*[@$!%*?&])[A-Za-z0-9@$!%*?&]{8,72}\z/';

if (!preg_match($passwordRegex, $password)) {
    showMessage(
        "Invalid Password",
        "Password must be 8–72 characters with a letter, number, and special character."
    );
}

if (!hash_equals($password, $confirmPassword)) {
    showMessage("Password Error", "Passwords do not match.");
}

$validCourses = ["BCA", "BBA", "B.Tech", "MCA", "MBA"];
$validYears = ["1", "2", "3", "4"];
$validGenders = ["Male", "Female", "Other"];

if (!in_array($course, $validCourses, true)) {
    showMessage("Invalid Course", "Please select a valid course.");
}

if (!in_array($year, $validYears, true)) {
    showMessage("Invalid Year", "Please select a valid year.");
}

if (!in_array($gender, $validGenders, true)) {
    showMessage("Invalid Gender", "Please select a valid gender.");
}

if ($terms !== "accepted") {
    showMessage("Terms Required", "You must accept the Terms and Conditions.");
}

try {
    $check = $conn->prepare(
        "SELECT username, email FROM users
         WHERE username = ? OR email = ?"
    );

    $check->bind_param("ss", $username, $email);
    $check->execute();

    $result = $check->get_result();

    $usernameExists = false;
    $emailExists = false;

    while ($row = $result->fetch_assoc()) {
        if (strcasecmp($row["username"], $username) === 0) {
            $usernameExists = true;
        }

        if (strcasecmp($row["email"], $email) === 0) {
            $emailExists = true;
        }
    }

    $check->close();

    if ($usernameExists && $emailExists) {
        showMessage(
            "Registration Failed",
            "Both username and email are already registered.",
            409
        );
    }

    if ($usernameExists) {
        showMessage(
            "Registration Failed",
            "This username is already taken. Please choose another.",
            409
        );
    }

    if ($emailExists) {
        showMessage(
            "Registration Failed",
            "This email is already registered. Please use another email.",
            409
        );
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    if ($passwordHash === false) {
        throw new RuntimeException("Password hashing failed.");
    }

    $yearNumber = (int) $year;

    $stmt = $conn->prepare(
        "INSERT INTO users
        (name, username, email, mobile, password, course, year, gender)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "ssssssis",
        $name,
        $username,
        $email,
        $mobile,
        $passwordHash,
        $course,
        $yearNumber,
        $gender
    );

    $stmt->execute();
    $stmt->close();
    $conn->close();

    unset($password, $confirmPassword, $passwordHash);

    showMessage(
        "Registration Successful!",
        "Your CHARUSAT account has been created. You can now proceed to login.",
        201
    );

} catch (mysqli_sql_exception $e) {
    error_log("Registration database error: " . $e->getMessage());

    if ((int) $e->getCode() === 1062) {
        showMessage(
            "Registration Failed",
            "This username or email is already registered.",
            409
        );
    }

    showMessage(
        "Registration Error",
        "Registration is temporarily unavailable. Please try again later.",
        500
    );

} catch (Throwable $e) {
    error_log("Registration error: " . $e->getMessage());

    showMessage(
        "Registration Error",
        "Unable to complete registration. Please try again later.",
        500
    );
}
?>
