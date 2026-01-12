<?php
header("Content-Type: application/json");
include "db.php"; // Database connection

// 🔹 Get POST data (JSON)
$data = json_decode(file_get_contents("php://input"), true);

// Department ID to delete
$department_id = intval($data['department_id'] ?? 0);

// ✅ Validation
if (!$department_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Department ID is required"
    ]);
    exit;
}

// 🔹 Delete query
$sql = "DELETE FROM departments WHERE id = $department_id";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        "status" => "success",
        "message" => "Department deleted successfully",
        "department_id" => $department_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete department: " . mysqli_error($conn)
    ]);
}
?>
