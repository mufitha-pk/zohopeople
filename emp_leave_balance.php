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

// 🔹 Only employees can access
if ($decoded->employee_type !== 'Employee') {
    http_response_code(403);
    echo json_encode(["status"=>"error","message"=>"Employees only"]);
    exit;
}

$emp_id = $decoded->emp_id;

// ==========================
// 1️⃣ Get all fixed leave types
// ==========================
$leave_types = [];
$result = $conn->query("
    SELECT 
        f.leave_policy_id AS fixed_entitlement_id, 
        f.name AS leave_name, 
        f.entitlement_value
    FROM fixed_entitlement f
    ORDER BY f.leave_policy_id
");

while ($row = $result->fetch_assoc()) {
    $leave_types[] = $row;
}

// ==========================
// 2️⃣ Prepare leave balance for each type
// ==========================
$response = [];
foreach ($leave_types as $leave) {
    $leave_type_id = $leave['fixed_entitlement_id'];
    $entitlement_value = $leave['entitlement_value'] ?? 0;

    // Approved leaves
    $stmt = $conn->prepare("
        SELECT SUM(DATEDIFF(date_to, date_from)+1) 
        FROM leave_requests
        WHERE emp_id = ? AND leave_type_id = ? AND status = 'Approved'
    ");
    $stmt->bind_param("ii", $emp_id, $leave_type_id);
    $stmt->execute();
    $stmt->bind_result($approved_days);
    $stmt->fetch();
    $stmt->close();
    $approved_days = $approved_days ?? 0;

    // Booked leaves (Approved + Pending)
    $stmt2 = $conn->prepare("
        SELECT SUM(DATEDIFF(date_to, date_from)+1) 
        FROM leave_requests
        WHERE emp_id = ? AND leave_type_id = ? AND status IN ('Approved','Pending')
    ");
    $stmt2->bind_param("ii", $emp_id, $leave_type_id);
    $stmt2->execute();
    $stmt2->bind_result($booked_days);
    $stmt2->fetch();
    $stmt2->close();
    $booked_days = $booked_days ?? 0;

    // Available leaves = entitlement - booked (approved + pending)
    $available = $entitlement_value - $booked_days;
    if ($available < 0) $available = 0;

    $response[] = [
        "leave_type_id" => $leave_type_id,
        "leave_name" => $leave['leave_name'],
        "entitlement" => $entitlement_value,
        "approved" => $approved_days,
        "booked" => $booked_days,
        "available" => $available
    ];
}

// ==========================
// 3️⃣ Return JSON response
// ==========================
echo json_encode([
    "status" => "success",
    "employee_id" => $emp_id,
    "leave_balance" => $response
]);

$conn->close();
?>
