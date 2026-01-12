<?php
header("Content-Type: application/json");

require 'vendor/autoload.php';
require 'db.php';  // your database connection
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";

// Get token from Authorization header
$headers = apache_request_headers();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (!$authHeader) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Authorization header missing"]);
    exit;
}

list($token) = sscanf($authHeader, 'Bearer %s');

if (!$token) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token not provided"]);
    exit;
}

// Verify JWT
try {
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
    $emp_id = $decoded->emp_id; // emp_id from JWT
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
    exit;
}

// ---------------------------
// 1️⃣ Fetch logged-in employee
$emp_sql = "SELECT emp_id, CONCAT(first_name, ' ', last_name) AS name, designation, photo
            FROM employee_registration
            WHERE emp_id = ?";
$stmt = $conn->prepare($emp_sql);
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fallback photo if null
if ($employee && !$employee['photo']) {
    $employee['photo'] = "/images/default_user.png";
}

// ---------------------------
// 2️⃣ Fetch manager info
$manager_sql = "SELECT CONCAT(first_name, ' ', last_name) AS name, photo
                FROM employee_registration
                WHERE emp_id = (SELECT reporting_to FROM employee_registration WHERE emp_id = ?)";
$stmt = $conn->prepare($manager_sql);
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$manager = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fallback photo for manager
if ($manager && !$manager['photo']) {
    $manager['photo'] = "/images/default_user.png";
}

// ---------------------------
// 3️⃣ Fetch reportees (with debug check)
$reportees_sql = "SELECT emp_id, CONCAT(first_name, ' ', last_name) AS name, designation, photo
                  FROM employee_registration
                  WHERE reporting_to = ?";
$stmt = $conn->prepare($reportees_sql);
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$reportees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Debug: show fetched reportees (optional, remove in production)
// var_dump($reportees);

foreach ($reportees as &$rep) {
    if (!$rep['photo']) {
        $rep['photo'] = "/images/default_user.png";
    }
}

$conn->close();

// ---------------------------
// Return final JSON
echo json_encode([
    "status" => "success",
    "employee" => $employee,
    "manager" => $manager,
    "reportees" => $reportees
]);
?>
