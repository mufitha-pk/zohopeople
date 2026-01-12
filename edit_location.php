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

$id            = intval($data['id'] ?? 0);
$location_name = trim($data['location_name'] ?? '');
$mail_alias    = trim($data['mail_alias'] ?? '');
$country       = trim($data['country'] ?? '');
$state         = trim($data['state'] ?? '');
$address       = trim($data['address'] ?? '');
$timezone      = trim($data['timezone'] ?? '');
$description   = trim($data['description'] ?? '');

if ($id <= 0 || !$location_name || !$mail_alias) {
    echo json_encode([
        "status" => "error",
        "message" => "Location ID, name and mail alias are required"
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

/* ================= UPDATE LOCATION ================= */
$stmt = $conn->prepare("
    UPDATE locations SET
        location_name = ?, 
        mail_alias = ?, 
        country = ?, 
        state = ?, 
        address = ?, 
        timezone = ?, 
        description = ?, 
        modified_by = ?, 
        modified_time = NOW()
    WHERE id = ?
");

$stmt->bind_param(
    "ssssssssi",
    $location_name,
    $mail_alias,
    $country,
    $state,
    $address,
    $timezone,
    $description,
    $username,
    $id
);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Location updated successfully",
        "modified_by" => $username,
        "id" => $id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update location: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
