<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json");
include "db.php"; // Your mysqli connection

// 🔹 JWT Authentication
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    echo json_encode(["status" => "error", "message" => "Token missing"]);
    exit;
}
$jwt = $matches[1];

$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));

    // Only Admin can add approvals
    if ($decoded->employee_type !== 'Admin') {
        echo json_encode(["status" => "error", "message" => "Only Admin can add approvals"]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Invalid token: " . $e->getMessage()]);
    exit;
}

// 🔹 Get POST data
$data = json_decode(file_get_contents("php://input"), true);

$approval_name       = trim($data['approval_name'] ?? '');
$form_name           = trim($data['form_name'] ?? '');
$description         = trim($data['description'] ?? '');
$criteria_name       = trim($data['criteria_name'] ?? '');
$criteria_type       = trim($data['criteria_type'] ?? '');
$criteria_condition  = trim($data['criteria_condition'] ?? '');
$approval_level      = intval($data['approval_level'] ?? 1);
$approver_type       = trim($data['approver_type'] ?? '');
$approver_value      = trim($data['approver_value'] ?? '');
$follow_up_enabled   = !empty($data['follow_up_enabled']) ? 1 : 0;
$follow_up_type      = trim($data['follow_up_type'] ?? '');
$follow_up_days      = intval($data['follow_up_days'] ?? 0);
$follow_up_time      = trim($data['follow_up_time'] ?? null);
$email_from          = trim($data['email_from'] ?? '');
$email_to            = trim($data['email_to'] ?? '');
$email_cc            = trim($data['email_cc'] ?? '');
$email_bcc           = trim($data['email_bcc'] ?? '');
$reply_to            = trim($data['reply_to'] ?? '');
$email_subject       = trim($data['email_subject'] ?? '');
$email_body          = trim($data['email_body'] ?? '');
$attachments         = trim($data['attachments'] ?? '');
$status              = (!empty($data['status']) && $data['status'] === 'active') ? 'active' : 'inactive';

// ✅ Basic Validation
if (!$approval_name || !$form_name) {
    echo json_encode(["status" => "error", "message" => "Approval name and Form name are required"]);
    exit;
}

// 🔹 Insert into leave_approval_form
$stmt = $conn->prepare("
    INSERT INTO leave_approval_form (
        approval_name, form_name, description, criteria_name, criteria_type, criteria_condition,
        approval_level, approver_type, approver_value, follow_up_enabled, follow_up_type,
        follow_up_days, follow_up_time, email_from, email_to, email_cc, email_bcc, reply_to,
        email_subject, email_body, attachments, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssssssississssssssssss",
    $approval_name, $form_name, $description, $criteria_name, $criteria_type, $criteria_condition,
    $approval_level, $approver_type, $approver_value, $follow_up_enabled, $follow_up_type,
    $follow_up_days, $follow_up_time, $email_from, $email_to, $email_cc, $email_bcc, $reply_to,
    $email_subject, $email_body, $attachments, $status
);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Approval added successfully", "approval_id" => $stmt->insert_id]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to add approval: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
