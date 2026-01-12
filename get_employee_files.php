<?php
include "db.php";

if (!isset($_GET['emp_file_id'])) {
    http_response_code(400);
    exit("file_id is required");
}

$emp_file_id = intval($_GET['emp_file_id']);

$sql = "SELECT upload_file, file_name FROM employee_files WHERE emp_file_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $emp_file_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit("File not found");
}

$file = $result->fetch_assoc();
$filePath = $file['upload_file'];

if (!file_exists($filePath)) {
    http_response_code(404);
    exit("File missing on server");
}

/* 🔴 VERY IMPORTANT HEADERS */
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"" . $file['file_name'] . "\"");
header("Content-Length: " . filesize($filePath));

readfile($filePath);
exit;


$stmt->close();
$conn->close();
?>
