<?php
// ================= CORS =================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ================= SESSION =================
session_start();

// ================= CHECK LOGIN =================
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in"
    ]);
    exit;
}

// ================= DB CONNECTION =================
include "db.php";

// ================= GET JSON DATA =================
$data = json_decode(file_get_contents("php://input"), true);

$stream_name    = trim($data['name'] ?? '');
$description    = trim($data['description'] ?? '');
$designation_id = intval($data['designation_id'] ?? 0); // expects ID from form

// ================= VALIDATION =================
if (!$stream_name) {
    echo json_encode([
        "status" => "error",
        "message" => "Stream name is required"
    ]);
    exit;
}

if (!$designation_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Designation is required"
    ]);
    exit;
}

// ================= GET LOGGED-IN USER INFO =================
$login_id = $_SESSION['emp_id'] ?? 0;
if (!$login_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Session login_id is missing"
    ]);
    exit;
}

// ================= FETCH USERNAME FROM employee_login =================
$added_by = '';
$stmt_name = $conn->prepare("SELECT username FROM employee_login WHERE id = ?");
$stmt_name->bind_param("i", $login_id);
$stmt_name->execute();
$result_name = $stmt_name->get_result();

if ($result_name && $result_name->num_rows > 0) {
    $row = $result_name->fetch_assoc();
    $added_by = $row['username'];
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Login ID $login_id not found in employee_login"
    ]);
    exit;
}
$stmt_name->close();

// ================= INSERT INTO STREAMS =================
$sql = "INSERT INTO streams (
    name,
    description,
    designation_id,
    login_id,
    added_by,
    added_time,
    modified_by,
    modified_time
) VALUES (?, ?, ?, ?, ?, NOW(), ?, NOW())";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Prepare failed: " . $conn->error
    ]);
    exit;
}

// ✅ Correct bind_param types: s = string, i = integer
$stmt->bind_param(
    "ssiiss",
    $stream_name,    // s
    $description,    // s
    $designation_id, // i
    $login_id,       // i
    $added_by,       // s
    $added_by        // s
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Stream added successfully",
        "stream_id" => $stmt->insert_id,
        "added_by" => $added_by,
        "login_id" => $login_id,
        "designation_id" => $designation_id
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
