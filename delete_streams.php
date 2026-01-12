<?php
// ================= CORS =================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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

// ================= DB CONNECTION =================
include "db.php"; // your mysqli connection

// ================= GET STREAM ID =================
$stream_id = intval($_GET['stream_id'] ?? ($_POST['stream_id'] ?? 0));

if ($stream_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid or missing stream_id. Pass ?stream_id=VALUE or JSON {\"stream_id\":VALUE}"
    ]);
    exit;
}

// ================= DELETE STREAM =================
$stmt = $conn->prepare("DELETE FROM streams WHERE stream_id = ?");
if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Prepare failed: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("i", $stream_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Stream deleted successfully",
            "stream_id" => $stream_id
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Stream not found or already deleted"
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Delete failed: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
