<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json");
include "db.php"; // Your MySQL connection

// 🔹 JWT Secret Key
$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";

// 🔹 Function to verify JWT
function verifyJWT($secret_key){
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if(!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)){
        echo json_encode(["status"=>"error","message"=>"Token missing"]);
        exit;
    }

    $jwt = $matches[1];

    try {
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
        return $decoded;
    } catch(Exception $e){
        echo json_encode(["status"=>"error","message"=>"Invalid token: ".$e->getMessage()]);
        exit;
    }
}

// 🔹 Verify JWT
$decoded = verifyJWT($secret_key);

// 🔹 Only Admin can delete role
if($decoded->employee_type !== 'Admin'){
    echo json_encode(["status"=>"error","message"=>"Only Admin can delete roles"]);
    exit;
}

// 🔹 Get POST data
$data = json_decode(file_get_contents("php://input"), true);
$id = intval($data['id'] ?? 0);

// ✅ Basic validation
if(!$id){
    echo json_encode(["status" => "error", "message" => "Role ID is required"]);
    exit;
}

// 🔹 Delete role
$stmt = $conn->prepare("DELETE FROM general_roles WHERE id=?");
$stmt->bind_param("i", $id);

if($stmt->execute()){
    echo json_encode([
        "status" => "success",
        "message" => "Role deleted successfully"
    ]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
