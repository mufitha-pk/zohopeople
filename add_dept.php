<?php
// ================= CORS =================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
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
include "db.php"; // your mysqli connection

// ================= GET POST DATA =================
$data = json_decode(file_get_contents("php://input"), true);

$department_name      = trim($data['department_name'] ?? '');
$department_head      = trim($data['department_head'] ?? '');
$mail_alias           = trim($data['mail_alias'] ?? '');
$parent_department_id = intval($data['parent_department_id'] ?? 0);

// ================= VALIDATION =================
if (!$department_name || !$department_head) {
    echo json_encode([
        "status" => "error",
        "message" => "Department name and department head are required"
    ]);
    exit;
}

// ================= GET EMPLOYEE NAME FROM LOGIN TABLE =================
$login_id = $_SESSION['emp_id'];
$added_by = ''; // default

$sql_name = "SELECT username FROM employee_login WHERE id = ?";
$stmt_name = $conn->prepare($sql_name);
$stmt_name->bind_param("i", $login_id);
$stmt_name->execute();
$result_name = $stmt_name->get_result();

if ($result_name && $result_name->num_rows > 0) {
    $row = $result_name->fetch_assoc();
    $added_by = $row['username']; // use login table username
} else {
    $added_by = $_SESSION['email']; // fallback if not found
}

$stmt_name->close();

// ================= INSERT INTO DEPARTMENTS =================
$sql = "INSERT INTO departments (
    department_name,
    department_head,
    mail_alias,
    parent_department_id,
    login_id,
    added_by,
    added_time,
    modified_by,
    modified_time
) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, NOW())";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Prepare failed: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "sssiiss",
    $department_name,
    $department_head,
    $mail_alias,
    $parent_department_id,
    $login_id,
    $added_by,
    $added_by
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Department added successfully",
        "department_id" => $stmt->insert_id,
        "added_by" => $added_by,
        "login_id" => $login_id
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
