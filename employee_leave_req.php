
<?php
header("Content-Type: application/json");
include "db.php";
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// 🔹 Secret key for JWT
$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";

// 🔹 Get JWT from Authorization header
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Token missing"]);
    exit;
}
$token = str_replace("Bearer ", "", $headers['Authorization']);

// 🔹 Decode JWT
try {
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Invalid token"]);
    exit;
}

// 🔹 Only employees can submit leave
if ($decoded->employee_type !== 'Employee') {
    http_response_code(403);
    echo json_encode(["status"=>"error","message"=>"Employees only"]);
    exit;
}

// 🔹 Get POST data
$data = json_decode(file_get_contents("php://input"), true);

// Required fields
$emp_id = $decoded->emp_id; // Use emp_id from JWT
$leave_policy_id = isset($data['leave_policy_id']) ? intval($data['leave_policy_id']) : null;
$leave_type_id = isset($data['leave_type_id']) ? $data['leave_type_id'] : null;
$date_from = isset($data['date_from']) ? $data['date_from'] : null;
$date_to = isset($data['date_to']) ? $data['date_to'] : null;

// Validate required fields
if (!$leave_policy_id || !$leave_type_id || !$date_from || !$date_to) {
    http_response_code(400);
    echo json_encode(["status"=>"error", "message"=>"leave_policy_id, leave_type_id, date_from & date_to are required"]);
    exit;
}

// Optional fields
$reason = $data['reason'] ?? null;
$status = 'Pending'; // Default status
$approved_by = null;
$date_of_joining = $data['date_of_joining'] ?? null;
$team_email_id = $data['team_email_id'] ?? null;
$reason_for_leave = $data['reason_for_leave'] ?? null;
$emergency_contact_no = $data['emergency_contact_no'] ?? null;
$medical_certification = $data['medical_certification'] ?? null;

// Insert query
$stmt = $conn->prepare("
    INSERT INTO leave_requests (
        emp_id, leave_policy_id, leave_type_id, date_from, date_to, reason, status, approved_by, 
        date_of_joining, team_email_id, reason_for_leave, emergency_contact_no, medical_certification
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "iiissssssssis",
    $emp_id,
    $leave_policy_id,
    $leave_type_id,
    $date_from,
    $date_to,
    $reason,
    $status,
    $approved_by,
    $date_of_joining,
    $team_email_id,
    $reason_for_leave,
    $emergency_contact_no,
    $medical_certification
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "leave_id" => $stmt->insert_id,
        "message" => "Leave request submitted successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
=======
