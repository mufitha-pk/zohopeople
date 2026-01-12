<?php
header("Content-Type: application/json");
include "db.php";

// Get POST JSON
$data = json_decode(file_get_contents("php://input"), true);

// Validate JSON
if (!$data) {
    echo json_encode(["status"=>"error","message"=>"Invalid or empty JSON"]);
    exit;
}

// Assign variables
$location_id     = intval($data['location_id'] ?? 0);
$week_start_day  = trim($data['week_start_day'] ?? '');
$work_week_start = trim($data['work_week_start'] ?? '');
$work_week_end   = trim($data['work_week_end'] ?? '');
$define_weekend  = trim($data['define_weekend'] ?? '');
$calendar_type   = trim($data['calendar_type'] ?? '');
$year_start      = trim($data['year_start'] ?? '');
$year_end        = trim($data['year_end'] ?? '');

// Validation
if (!$location_id || !$week_start_day || !$work_week_start || !$work_week_end || !$calendar_type || !$year_start || !$year_end) {
    echo json_encode(["status"=>"error","message"=>"Required fields are missing"]);
    exit;
}

// Validate date format
$date_regex = "/^\d{4}-\d{2}-\d{2}$/";
if (!preg_match($date_regex, $year_start) || !preg_match($date_regex, $year_end)) {
    echo json_encode(["status"=>"error","message"=>"Invalid date format. Use YYYY-MM-DD"]);
    exit;
}

// Insert into table
$sql = "INSERT INTO work_calendar 
(location_id, week_start_day, work_week_start, work_week_end, define_weekend, calendar_type, year_start, year_end)
VALUES
($location_id, '$week_start_day', '$work_week_start', '$work_week_end', '$define_weekend', '$calendar_type', '$year_start', '$year_end')";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        "status" => "success",
        "message" => "Calendar added successfully",
        "calendar_id" => mysqli_insert_id($conn)
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to add calendar: " . mysqli_error($conn)
    ]);
}
var_dump($calendar_type); // check what value PHP sees
exit;

?>
