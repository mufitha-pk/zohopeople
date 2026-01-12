<?php
header("Content-Type: application/json");

require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include "db.php"; // mysqli connection

/* ================= JWT AUTH ================= */

$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";

// Get Authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Authorization token missing"]);
    exit;
}

$token = $matches[1];

// Verify JWT
try {
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));

    // ✅ Allow only Admin
    if ($decoded->employee_type !== 'Admin') {
        http_response_code(403);
        echo json_encode(["status"=>"error","message"=>"Access denied, Admin only"]);
        exit;
    }

    $created_by = $decoded->emp_id;

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Invalid or expired token"]);
    exit;
}

/* ================= INPUT ================= */

$data = json_decode(file_get_contents("php://input"), true);

$form_name             = trim($data['form_name'] ?? '');
$template_name         = trim($data['template_name'] ?? '');
$available_merge_field = trim($data['available_merge_field'] ?? '');
$select_field          = trim($data['select_field'] ?? '');
$field_value           = trim($data['field_value'] ?? '');
$message               = trim($data['message'] ?? '');

/* ================= VALIDATION ================= */

if (
    !$form_name || !$template_name || !$available_merge_field ||
    !$select_field || !$field_value || !$message
) {
    echo json_encode(["status"=>"error","message"=>"All fields are required"]);
    exit;
}

/* ================= INSERT ================= */

$sql = "INSERT INTO email_templates
        (form_name, template_name, available_merge_field, select_field, field_value, message)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssss",
    $form_name,
    $template_name,
    $available_merge_field,
    $select_field,
    $field_value,
    $message
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Email template added successfully",
        "template_id" => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Insert failed: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
