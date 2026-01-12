<?php
header("Content-Type: application/json");

require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include "db.php"; // Your mysqli connection

/* ================= JWT AUTH ================= */

$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Authorization token missing"]);
    exit;
}

$token = $matches[1];

try {
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));

    // ✅ Admin only
    if ($decoded->employee_type !== 'Admin') {
        http_response_code(403);
        echo json_encode(["status"=>"error","message"=>"Access denied, Admin only"]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Invalid or expired token"]);
    exit;
}

/* ================= FETCH EMAIL TEMPLATES ================= */

$sql = "SELECT id, form_name, template_name, available_merge_field, select_field, field_value, message 
        FROM email_templates
        ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

$templates = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $templates[] = $row;
    }
}

echo json_encode([
    "status" => "success",
    "templates" => $templates
]);

$conn->close();
?>
