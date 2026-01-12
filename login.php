<?php
// ================= CORS =================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

// ================= SESSION =================
session_start();

// ================= DB =================
include "db.php";

// ================= INPUT =================
$data = json_decode(file_get_contents("php://input"), true);

$email    = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

// ================= VALIDATION =================
if (!$email || !$password) {
    echo json_encode([
        "status" => "error",
        "message" => "Email and password required"
    ]);
    exit;
}

// ================= CHECK USER =================
$sql = "SELECT * FROM employee_login WHERE email_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email or password"
    ]);
    exit;
}

$user = $result->fetch_assoc();

// ================= STATUS CHECK =================
if ($user['employee_status'] !== 'active') {
    echo json_encode([
        "status" => "error",
        "message" => "Your account is inactive. Please contact admin."
    ]);
    exit;
}

// ================= PASSWORD CHECK =================
if ($password !== $user['password']) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email or password"
    ]);
    exit;
}

// ================= STORE SESSION =================
$_SESSION['logged_in']     = true;
$_SESSION['emp_id']        = $user['id'];
$_SESSION['email']         = $user['email_id'];
$_SESSION['employee_type'] = $user['employee_type'];
$_SESSION['username'] = $user['username'];
$_SESSION['login_time']    = time();

echo json_encode([
    "status" => "success",
    "message" => "Login successful",
    "employee_type" => $user['employee_type']
]);
?>
