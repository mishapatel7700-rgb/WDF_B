<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Database credentials
$host = "localhost";
$username = "root"; 
$password = "";     
$dbname = "charusat_portal";

// Connect to MySQL
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $sql = "SELECT * FROM api_events ORDER BY event_date DESC";
        $result = $conn->query($sql);
        $events = [];
        
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                "id" => (int)$row['id'],
                "title" => $row['title'],
                "date" => $row['event_date'],
                "description" => $row['description'],
                "category" => "Spoural Event"
            ];
        }
        echo json_encode($events);
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!empty($data['title']) && !empty($data['date']) && !empty($data['description'])) {
            $stmt = $conn->prepare("INSERT INTO api_events (title, event_date, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $data['title'], $data['date'], $data['description']);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "id" => $conn->insert_id]);
            } else {
                echo json_encode(["error" => "Failed to save event"]);
            }
            $stmt->close();
        } else {
            echo json_encode(["error" => "Invalid inputs provided"]);
        }
        break;

    case 'DELETE':
        $id = 0;
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
        }

        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM api_events WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success"]);
            } else {
                echo json_encode(["error" => "Failed to delete event"]);
            }
            $stmt->close();
        } else {
            echo json_encode(["error" => "Invalid or missing ID"]);
        }
        break;
}

$conn->close();
?>