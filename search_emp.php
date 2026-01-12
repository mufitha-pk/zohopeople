<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include "db.php"; 


$searchText = isset($_GET['query']) ? $_GET['query'] : "";


$stmt = $conn->prepare("SELECT emp_id, first_name FROM employee_registration WHERE first_name LIKE CONCAT('%', ?, '%')");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Prepare failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("s", $searchText);
$stmt->execute();
$result = $stmt->get_result();


$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = $row;
}


echo json_encode($employees);


$stmt->close();
$conn->close();
?>
