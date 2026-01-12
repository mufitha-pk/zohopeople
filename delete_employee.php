<?php
header("Content-Type: application/json");
include "db.php"; // Database connection

// 🔹 Get POST data (JSON)
$data = json_decode(file_get_contents("php://input"), true);

$emp_id = intval($data['emp_id'] ?? 0);

if (!$emp_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Employee ID is required"
    ]);
    exit;
}

// 🔹 Delete from employee_registration first
$sql_reg = "DELETE FROM employee_registration WHERE emp_id=$emp_id";
$reg_deleted = mysqli_query($conn, $sql_reg);

// 🔹 Delete from employee_login
$sql_login = "DELETE FROM employee_login WHERE id=$emp_id";
$login_deleted = mysqli_query($conn, $sql_login);

if ($reg_deleted && $login_deleted) {
    echo json_encode([
        "status" => "success",
        "message" => "Employee deleted successfully",
        "emp_id" => $emp_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete employee: " . mysqli_error($conn)
    ]);
}
?>
