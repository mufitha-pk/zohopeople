<?php
header("Content-Type: application/json");
include "db.php"; // Database connection

// 🔹 Fetch all departments
$sql = "SELECT 
            id,
            department_name,
            mail_alias,
            added_by,
            added_time,
            modified_by,
            modified_time
        FROM departments
        ORDER BY id ASC";

$result = mysqli_query($conn, $sql);

if ($result) {
    $departments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $departments[] = [
            "id" => $row['id'],
            "department_name" => $row['department_name'],
            "mail_alias" => $row['mail_alias'],
            "added_by" => $row['added_by'],
            "added_time" => $row['added_time'],
            "modified_by" => $row['modified_by'],
            "modified_time" => $row['modified_time']
        ];
    }

    echo json_encode([
        "status" => "success",
        "departments" => $departments
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch departments: " . mysqli_error($conn)
    ]);
}
?>
