<?php
header("Content-Type: application/json");
include "db.php";
session_start();



echo json_encode($_SESSION);

$searchText = trim($_GET['query'] ?? '');
if($searchText){
    $stmt = $conn->prepare("
        SELECT l.id AS emp_id, r.first_name 
        FROM employee_login l
        INNER JOIN employee_registration r ON l.id = r.emp_id
        WHERE r.first_name LIKE CONCAT('%', ?, '%')
        ORDER BY r.first_name ASC
    ");
    $stmt->bind_param("s", $searchText);
}else{
    $stmt = $conn->prepare("
        SELECT l.id AS emp_id, r.first_name 
        FROM employee_login l
        INNER JOIN employee_registration r ON l.id = r.emp_id
        ORDER BY r.first_name ASC
    ");
}


$stmt->execute();
$result = $stmt->get_result();

$employees = [];
while($row = $result->fetch_assoc()){
    $employees[] = $row;
}

echo json_encode($employees);


$stmt->close();
$conn->close();
?>
