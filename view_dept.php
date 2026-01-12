<?php
header("Content-Type: application/json");
include "db.php";
session_start(); // Start session

// ================= SESSION AUTH CHECK =================
// if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true){
//     echo json_encode([
//         "status" => "error",
//         "message" => "User not logged in"
//     ]);
//     exit;
// }
 echo json_encode($_SESSION);

// ================= INPUT =================
$department_id = intval($_GET['id'] ?? 0);

if (!$department_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Department ID required"
    ]);
    exit;
}

// ================= FETCH =================
$sql = "SELECT 
    department_name,
    department_head,
    mail_alias,
    parent_department_id,
    added_by,
    added_time,
    modified_by,
    modified_time
FROM departments
WHERE id = $department_id";

$res = mysqli_query($conn, $sql);

if ($res && mysqli_num_rows($res) > 0) {
    echo json_encode([
        "status" => "success",
        "data" => mysqli_fetch_assoc($res)
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Department not found"
    ]);
}

// Close connection
$conn->close();
?>
