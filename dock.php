<?php
header("Content-Type: application/json");
include "db.php"; 

// Get department ID from URL
$department_id = intval($_GET['id'] ?? 0);

if($department_id <= 0){
    echo json_encode(["status"=>"error","message"=>"Invalid department ID"]);
    exit;
}

$sql = "SELECT 
            id,
            department_name,
            department_head,
            mail_alias,
            parent_department_id,
            added_by,
            added_time,
            modified_by,
            modified_time
        FROM departments
        WHERE id=?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0){
    echo json_encode([
        "status"=>"success",
        "data"=>$result->fetch_assoc()
    ]);
} else {
    echo json_encode([
        "status"=>"error",
        "message"=>"Department not found"
    ]);
}
?>