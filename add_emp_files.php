<?php
// add_employee_file.php
header("Content-Type: application/json");

// ---------------- Dependencies ----------------
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include 'db.php'; // MySQL connection ($conn)

// ---------------- JWT Secret ----------------
$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";

// ---------------- JWT Authentication ----------------
$headers = getallheaders();
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $headers['Authorization'] ?? '';

if (!$authHeader) {
    http_response_code(401);
    die(json_encode(["error" => "Authorization header missing"]));
}

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    die(json_encode(["error" => "Invalid Authorization header"]));
}

$jwt = $matches[1];

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    $emp_id = $decoded->emp_id ?? null;
    if (!$emp_id) {
        http_response_code(401);
        die(json_encode(["error" => "emp_id missing in token"]));
    }
} catch (Exception $e) {
    http_response_code(401);
    die(json_encode(["error" => "Invalid or expired token"]));
}

// ---------------- Handle File Upload ----------------
if (!isset($_FILES['file'])) {
    http_response_code(400);
    die(json_encode(["error" => "No file uploaded."]));
}

$uploadDir = "uploads/employee_files/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$fileTmp  = $_FILES['file']['tmp_name'];
$fileName = basename($_FILES['file']['name']);
$filePath = $uploadDir . time() . "_" . $fileName;

// Optional: limit PDF size (<10MB) and allowed types
$allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
if (!in_array($_FILES['file']['type'], $allowedTypes)) {
    http_response_code(400);
    die(json_encode(["error" => "Invalid file type. Only PDF, DOCX, XLS allowed."]));
}

if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
    http_response_code(400);
    die(json_encode(["error" => "File too large. Maximum 10MB allowed."]));
}

if (!move_uploaded_file($fileTmp, $filePath)) {
    http_response_code(500);
    die(json_encode(["error" => "Failed to move uploaded file."]));
}

// ---------------- Get Other Form Fields ----------------
$file_access                 = $_POST['file_access'] ?? 'Active Employee';
$file_access_value           = $_POST['file_access_value'] ?? null;
$description                 = $_POST['description'] ?? null;
$folder                      = $_POST['folder'] ?? null;
$file_expiry_date             = $_POST['file_expiry_date'] ?? null;
$acknowledgement_value        = $_POST['acknowledgement_value'] ?? 'FALSE';
$enforce_mandatory_deadline   = $_POST['enforce_mandatory_deadline'] ?? '0';
$acknowledgement_deadline     = $_POST['acknowledgement_deadline'] ?? null;
$notify_feeds                 = $_POST['notify_feeds'] ?? 1;
$notify_email                 = $_POST['notify_email'] ?? 1;

// ---------------- Handle view_access and download_access safely ----------------
$view_access = $_POST['view_access'] ?? 'Employee';
if (is_array($view_access)) $view_access = implode(',', $view_access);

$download_access = $_POST['download_access'] ?? 'Employee';
if (is_array($download_access)) $download_access = implode(',', $download_access);

// ---------------- Insert into Database ----------------
$stmt = $conn->prepare("INSERT INTO employee_files (
    upload_file, file_name, file_access, file_acces_value, description, folder,
    file_expiry_date, acknowledgement_value, enforce_mandatory_deadline,
    acknowledgement_deadline, view_access, download_access, notify_feeds, notify_email
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "ssssssssssssii",
    $filePath, $fileName, $file_access, $file_access_value, $description, $folder,
    $file_expiry_date, $acknowledgement_value, $enforce_mandatory_deadline,
    $acknowledgement_deadline, $view_access, $download_access, $notify_feeds, $notify_email
);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Employee file uploaded successfully!",
        "file_path" => $filePath
    ]);
} else {
    http_response_code(500);
    echo json_encode(["error" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
