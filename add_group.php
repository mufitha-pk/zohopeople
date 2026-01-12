<?php
// ================= CORS =================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

// ================= SESSION =================
session_start();

// ================= CHECK LOGIN =================
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in"
    ]);
    exit;
}

// ================= DB =================
include "db.php";

// ================= READ JSON INPUT =================
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!$data) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid or empty JSON input"
    ]);
    exit;
}

// ================= INPUT FIELDS =================
$group_name = trim($data['group_name'] ?? '');
$description = trim($data['description'] ?? '');
$group_email = trim($data['group_email'] ?? '');
$admins = $data['administrators_id'] ?? [];
$members = $data['members_id'] ?? [];
$notify_new_members = isset($data['notify_new_members']) ? (int)$data['notify_new_members'] : 1;

// ================= VALIDATION =================
if ($group_name === '') {
    echo json_encode([
        "status" => "error",
        "message" => "Group name is required"
    ]);
    exit;
}

if (!is_array($admins) || !is_array($members)) {
    echo json_encode([
        "status" => "error",
        "message" => "Administrators and members must be arrays"
    ]);
    exit;
}

// ================= CONVERT TO JSON =================
$admins_json = json_encode($admins);
$members_json = json_encode($members);

// ================= INSERT =================
$sql = "INSERT INTO groups 
(group_name, description, group_email, administrators_id, members_id, notify_new_members)
VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "sssssi",
    $group_name,
    $description,
    $group_email,
    $admins_json,
    $members_json,
    $notify_new_members
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Group created successfully",
        "group_id" => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>