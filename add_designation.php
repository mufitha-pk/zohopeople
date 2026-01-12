<?php
header("Content-Type: application/json");
include "db.php";

// ================= SESSION =================
session_start();

// ================= AUTH CHECK =================
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in"
    ]);
    exit;
}

// ================= ADMIN CHECK =================
if (!isset($_SESSION['employee_type']) || $_SESSION['employee_type'] !== 'Admin') {
    http_response_code(403);
    echo json_encode([
        "status" => "error",
        "message" => "Access denied. Admin only."
    ]);
    exit;
}

// ================= INPUT =================
$data = json_decode(file_get_contents("php://input"), true);

$designation_name = trim($data['designation_name'] ?? '');
$mail_alias       = trim($data['mail_alias'] ?? '');
$stream_id        = intval($data['stream_id'] ?? 0);

if ($designation_name === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Designation name is required"
    ]);
    exit;
}

// ================= LOGIN INFO =================
$login_id = $_SESSION['emp_id']; // employee_login.id

// ================= FETCH USERNAME =================
$userSql = "SELECT username FROM employee_login WHERE id = ?";
$stmt = mysqli_prepare($conn, $userSql);
mysqli_stmt_bind_param($stmt, "i", $login_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid login user"
    ]);
    exit;
}

$username = $row['username'];

// ================= INSERT DESIGNATION =================
$insertSql = "INSERT INTO designation (
    designation_name,
    mail_alias,
    stream_id,
    login_id,
    added_by,
    added_time,
    modified_by,
    modified_time
) VALUES (?, ?, ?, ?, ?, NOW(), ?, NOW())";

$insertStmt = mysqli_prepare($conn, $insertSql);
mysqli_stmt_bind_param(
    $insertStmt,
    "ssiiss",
    $designation_name,
    $mail_alias,
    $stream_id,
    $login_id,
    $username,
    $username
);

if (mysqli_stmt_execute($insertStmt)) {
    echo json_encode([
        "status" => "success",
        "message" => "Designation added successfully",
        "designation_id" => mysqli_insert_id($conn),
        "added_by" => $username
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to add designation"
    ]);
}
?>
