<?php
header("Content-Type: application/json");
include "db.php";
session_start();


// if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true){
//     echo json_encode(["status"=>"error","message"=>"User not logged in"]);
//     exit;
// }

echo json_encode($_SESSION);

$searchText = trim($_GET['query'] ?? '');

if($searchText){
    $stmt = $conn->prepare("
        SELECT id, department_name
        FROM departments
        WHERE department_name LIKE CONCAT('%', ?, '%')
        ORDER BY department_name ASC
    ");
    $stmt->bind_param("s", $searchText);
}else{
    $stmt = $conn->prepare("
        SELECT id, department_name
        FROM departments
        ORDER BY department_name ASC
    ");
}


$stmt->execute();
$result = $stmt->get_result();

$departments = [];
while($row = $result->fetch_assoc()){
    $departments[] = $row;
}


echo json_encode($departments);


$stmt->close();
$conn->close();
?>
