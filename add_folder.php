<?php
header("Content-Type: application/json");

include "db.php";
require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/* ================= JWT CONFIG ================= */
$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";

/* ================= READ AUTH HEADER ================= */
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!$authHeader) {
    http_response_code(401);
    echo json_encode(["error" => "Authorization header missing"]);
    exit;
}

if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid Authorization format"]);
    exit;
}

$jwt = $matches[1];

/* ================= DECODE JWT ================= */
try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    $emp_id = $decoded->emp_id ?? null;

    if (!$emp_id) {
        http_response_code(401);
        echo json_encode(["error" => "emp_id missing in token"]);
        exit;
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid or expired token"]);
    exit;
}

/* ================= READ JSON INPUT ================= */
$data = json_decode(file_get_contents("php://input"), true);

$folder_name      = trim($data['folder_name'] ?? '');
$place_under      = $data['place_under'] ?? null;
$parent_folder_id = $data['parent_folder_id'] ?? null;

/* ================= VALIDATION ================= */
if ($folder_name === '') {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Folder name is required"
    ]);
    exit;
}

/* ================= INSERT QUERY ================= */
$sql = "INSERT INTO add_folders (folder_name, place_under, parent_folder_id)
        VALUES (?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $folder_name, $place_under, $parent_folder_id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Folder created successfully",
        "folder_id" => $stmt->insert_id,
        "created_by" => $emp_id
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
