<?php
// add_organization.php
header("Content-Type: application/json");

require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include 'db.php'; // Your MySQL connection

$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";

// 🔹 Get Authorization header
$headers = getallheaders();
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] 
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] 
            ?? $headers['Authorization'] ?? '';

if (!$authHeader) {
    http_response_code(401);
    die(json_encode(["error" => "Authorization header missing"]));
}

// 🔹 Extract token
if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    die(json_encode(["error" => "Invalid Authorization header"]));
}

$jwt = $matches[1];

// 🔹 Decode JWT
try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    $user_id = $decoded->emp_id ?? null; // Matches your login.php payload
    if (!$user_id) {
        http_response_code(401);
        die(json_encode(["error" => "emp_id missing in token"]));
    }
} catch (Exception $e) {
    http_response_code(401);
    die(json_encode(["error" => "Invalid or expired token"]));
}

// 🔹 Get JSON input
$input = json_decode(file_get_contents("php://input"), true);

// Default values if keys are missing
$upload_file               = $input['upload_file'] ?? '';
$file_name                 = $input['file_name'] ?? '';
$description               = $input['description'] ?? '';
$share_with                = $input['share_with'] ?? '';
$folder                    = $input['folder'] ?? '';
$file_expiry_date           = $input['file_expiry_date'] ?? null;
$is_policy_document         = isset($input['is_policy_document']) ? 1 : 0;
$acknowledgement_consent    = isset($input['acknowledgement_consent']) ? 1 : 0;
$acknowledgement_value      = $input['acknowledgement_value'] ?? '';
$enforce_mandatory_deadline = isset($input['enforce_mandatory_deadline']) ? 1 : 0;
$acknowledgement_deadline   = $input['acknowledgement_deadline'] ?? null;
$download_access            = isset($input['download_access']) ? 1 : 0;
$notify_feeds               = isset($input['notify_feeds']) ? 1 : 0;
$notify_email               = isset($input['notify_email']) ? 1 : 0;

// 🔹 Insert into DB
$stmt = $conn->prepare("INSERT INTO organization_files (
    upload_file, file_name, description, share_with, folder,
    file_expiry_date, is_policy_document, acknowledgement_consent,
    acknowledgement_value, enforce_mandatory_deadline, acknowledgement_deadline,
    download_access, notify_feeds, notify_email
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param(
    "ssssssiiissiis",
    $upload_file, $file_name, $description, $share_with, $folder,
    $file_expiry_date, $is_policy_document, $acknowledgement_consent,
    $acknowledgement_value, $enforce_mandatory_deadline, $acknowledgement_deadline,
    $download_access, $notify_feeds, $notify_email
);

// 🔹 Execute and return response
if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "File added successfully!"]);
} else {
    http_response_code(500);
    echo json_encode(["error" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
