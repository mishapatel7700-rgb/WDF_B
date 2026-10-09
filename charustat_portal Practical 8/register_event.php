<?php
header("Content-Type: application/json; charset=UTF-8");

function respond($success, $message, $status = 200) {
    http_response_code($status);
    echo json_encode(["success" => $success, "message" => $message]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(false, "Invalid request method.", 405);
}

$eventId = filter_var($_POST["event_id"] ?? null, FILTER_VALIDATE_INT);
$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone = trim($_POST["phone"] ?? "");

if (!$eventId || $eventId < 1) respond(false, "Please select a valid event.", 400);
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) respond(false, "Enter a name between 2 and 100 characters.", 400);
if (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) respond(false, "Enter a valid email address.", 400);
if (!preg_match("/^[0-9]{10}$/", $phone)) respond(false, "Phone number must contain exactly 10 digits.", 400);

require "db.php";

try {
    $check = $conn->prepare("SELECT id FROM events WHERE id = ?");
    $check->bind_param("i", $eventId);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        $check->close();
        $conn->close();
        respond(false, "This event does not exist.", 404);
    }
    $check->close();

    $insert = $conn->prepare(
        "INSERT INTO event_registrations (event_id, name, email, phone) VALUES (?, ?, ?, ?)"
    );
    $insert->bind_param("isss", $eventId, $name, $email, $phone);
    $insert->execute();
    $insert->close();
    $conn->close();
    respond(true, "Registration successful!");
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    $conn->close();
    if ((int)$e->getCode() === 1062) {
        respond(false, "You have already registered for this event with this email.", 409);
    }
    respond(false, "Unable to save registration. Check that database.sql was imported.", 500);
}
?>
