<?php
header("Content-Type: application/json");
include "db.php"; // Include your database connection

// 🔹 Get POST data (JSON)
$data = json_decode(file_get_contents("php://input"), true);

// Assign variables
$location_id     = intval($data['location_id'] ?? 0);
$holiday_name    = trim($data['holiday_name'] ?? '');
$holiday_date    = trim($data['holiday_date'] ?? '');
$classification  = trim($data['classification'] ?? 'Holiday'); // Default 'Holiday'
$description     = trim($data['description'] ?? '');
$reminder_days   = intval($data['reminder_days'] ?? 0);

// ✅ Validation
if ($location_id === 0 || !$holiday_name || !$holiday_date) {
    echo json_encode([
        "status" => "error",
        "message" => "Required fields are missing"
    ]);
    exit;
}

// 🔹 Validate date format YYYY-MM-DD
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $holiday_date)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid date format. Use YYYY-MM-DD"
    ]);
    exit;
}

// 🔹 Insert into holidays table
$sql = "INSERT INTO holidays (location_id, holiday_name, holiday_date, classification, description, reminder_days)
        VALUES ($location_id, '$holiday_name', '$holiday_date', '$classification', '$description', $reminder_days)";

if (mysqli_query($conn, $sql)) {
    $holiday_id = mysqli_insert_id($conn);
    echo json_encode([
        "status" => "success",
        "message" => "Holiday added successfully",
        "holiday_id" => $holiday_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to add holiday: " . mysqli_error($conn)
    ]);
}
?>
