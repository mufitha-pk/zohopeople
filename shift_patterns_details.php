<?php
// shift_pattern_details.php
header("Content-Type: application/json");

// Include your database connection
include "db.php"; // Make sure this file defines $conn as mysqli connection

// Get POST data (raw JSON)
$data = json_decode(file_get_contents("php://input"), true);

$pattern_id = intval($data['pattern_id'] ?? 0);
$week_number = intval($data['week_number'] ?? 1);
$shifts = $data['shifts'] ?? []; // Expected: ["Sunday" => 1, "Monday" => 2, ...]

if ($pattern_id <= 0 || empty($shifts)) {
    echo json_encode(['status' => 'error', 'message' => 'Pattern ID and shifts are required']);
    exit;
}

// Prepare insert statement
$stmt = $conn->prepare("INSERT INTO shift_pattern_details (pattern_id, day_of_week, shift_id, week_number) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => $conn->error]);
    exit;
}

foreach ($shifts as $day => $shift_id) {
    $shift_id = !empty($shift_id) ? intval($shift_id) : null; // NULL for day off
    $stmt->bind_param("isii", $pattern_id, $day, $shift_id, $week_number);
    $stmt->execute();
}

$stmt->close();

echo json_encode(['status' => 'success', 'message' => 'Shift pattern details added successfully']);
?>
