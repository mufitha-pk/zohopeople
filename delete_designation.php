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

echo json_encode($_SESSION);

// ================= INPUT =================
$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);

if ($id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid designation ID"
    ]);
    exit;
}

// ================= DELETE QUERY =================
$deleteSql = "DELETE FROM designation WHERE id = ?";
$stmt = mysqli_prepare($conn, $deleteSql);
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Designation deleted successfully",
            "id" => $id
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Designation not found or already deleted"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to delete designation"
    ]);
}
?>
