<?php
header("Content-Type: application/json");
include "db.php"; // Make sure this file contains your DB connection ($conn)

// 🔹 Get POST data (JSON)
$data = json_decode(file_get_contents("php://input"), true);

$shift_name               = trim($data['shift_name'] ?? '');
$color                    = trim($data['color'] ?? '');
$from_time                = trim($data['from_time'] ?? '');
$to_time                  = trim($data['to_time'] ?? '');
$shift_margin             = trim($data['shift_margin'] ?? '');
$core_start_time          = trim($data['core_start_time'] ?? '');
$core_end_time            = trim($data['core_end_time'] ?? '');
$weekends_basis           = trim($data['weekends_basis'] ?? '');
$location_id              = intval($data['location_id'] ?? 0);
$provide_shift_allowance  = isset($data['provide_shift_allowance']) ? intval($data['provide_shift_allowance']) : 0;

// 🔹 Basic validation
if (empty($shift_name) || empty($from_time) || empty($to_time) || $location_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields: shift_name, from_time, to_time, location_id"
    ]);
    exit;
}

// 🔹 Insert query using prepared statements
$sql = "INSERT INTO shift
(shift_name, color, from_time, to_time, shift_margin, core_start_time, core_end_time, weekends_basis, location_id, provide_shift_allowance) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssssssii",
    $shift_name,
    $color,
    $from_time,
    $to_time,
    $shift_margin,
    $core_start_time,
    $core_end_time,
    $weekends_basis,
    $location_id,
    $provide_shift_allowance
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Shift added successfully",
        "shift_id" => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
