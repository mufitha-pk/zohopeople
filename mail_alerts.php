<?php
header("Content-Type: application/json");

include "db.php"; // mysqli connection
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/* ================= JWT AUTH ================= */
$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Token missing"]);
    exit;
}

$jwt = $matches[1];

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    $emp_id = $decoded->emp_id; // logged-in employee
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid or expired token"]);
    exit;
}

/* ================= FETCH FEEDS WITH FROM EMAIL ================= */
// actor_id in feeds corresponds to email_alerts.id
$sql = "SELECT f.id AS feed_id,
               f.message,
               f.created_at AS sending_time,
               ea.from_email
        FROM feeds f
        LEFT JOIN email_alerts ea ON f.actor_id = ea.id
        WHERE f.emp_id = ?
        ORDER BY f.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$result = $stmt->get_result();

$alerts = [];
while ($row = $result->fetch_assoc()) {
    $alerts[] = [
        "feed_id"      => $row['feed_id'],
        "message"      => $row['message'],
        "from_email"   => $row['from_email'] ?? "system@example.com",
        "sending_time" => $row['sending_time']
    ];
}

echo json_encode([
    "status" => "success",
    "data" => $alerts
]);

$conn->close();
?>
