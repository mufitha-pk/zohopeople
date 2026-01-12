<?php
header("Content-Type: application/json");
include "db.php";
session_start();

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in"
    ]);
    exit;
}

/* ================= REQUEST METHOD ================= */
if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method"
    ]);
    exit;
}

/* ================= GET POST DATA ================= */
$data = json_decode(file_get_contents("php://input"), true);

$location_name = trim($data['location_name'] ?? '');
$mail_alias    = trim($data['mail_alias'] ?? '');
$country       = trim($data['country'] ?? '');
$state         = trim($data['state'] ?? '');
$address       = trim($data['address'] ?? '');
$timezone      = trim($data['timezone'] ?? '');
$description   = trim($data['description'] ?? '');

/* ================= VALIDATION ================= */
if (!$location_name || !$mail_alias) {
    echo json_encode([
        "status" => "error",
        "message" => "Location name and mail alias are required"
    ]);
    exit;
}

/* ================= GET LOGGED-IN USER INFO ================= */
$login_id = $_SESSION['emp_id'];
$stmt_user = $conn->prepare("SELECT username FROM employee_login WHERE id = ?");
$stmt_user->bind_param("i", $login_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$username = ($result_user && $result_user->num_rows > 0) ? $result_user->fetch_assoc()['username'] : "Unknown";
$stmt_user->close();

/* ================= INSERT LOCATION ================= */
$stmt = $conn->prepare("
    INSERT INTO locations 
        (location_name, mail_alias, country, state, address, timezone, description, login_id, added_by, added_time, modified_by, modified_time)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW())
");

$stmt->bind_param(
    "ssssssssss",
    $location_name,
    $mail_alias,
    $country,
    $state,
    $address,
    $timezone,
    $description,
    $login_id,
    $username,
    $username // modified_by initially same as added_by
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Location added successfully",
        "location_name" => $location_name,
        "added_by" => $username,
        "modified_by" => $username,
        "id" => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to add location: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
