<?php
header("Content-Type: application/json; charset=UTF-8");
require "db.php";

try {
    $sql = "SELECT id, title, event_date, category, description, image
            FROM events ORDER BY event_date ASC";
    $result = $conn->query($sql);
    $events = [];

    while ($row = $result->fetch_assoc()) {
        $events[] = [
            "id" => (int)$row["id"],
            "title" => $row["title"],
            "date" => $row["event_date"],
            "category" => $row["category"],
            "description" => $row["description"],
            "image" => $row["image"]
        ];
    }

    echo json_encode($events, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $conn->close();
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Unable to load events. Import database.sql first."]);
}
?>
