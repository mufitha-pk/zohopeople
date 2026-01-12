<?php
header("Content-Type: application/json");
require 'db.php';
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// JWT AUTH
$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
if(!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)){
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Token missing"]);
    exit;
}
$jwt = $matches[1];
try{
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    if($decoded->employee_type !== 'Admin'){
        http_response_code(403);
        echo json_encode(["status"=>"error","message"=>"Admin only"]);
        exit;
    }
} catch(Exception $e){
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Invalid token"]);
    exit;
}

// POST DATA
$data = json_decode(file_get_contents("php://input"), true);
$alert_name    = trim($data['alert_name'] ?? '');
$description   = trim($data['description'] ?? '');
$form_name     = trim($data['form_name'] ?? '');
$from_email    = trim($data['from_email'] ?? '');
$to_email      = trim($data['to_email'] ?? '');
$cc_email      = trim($data['cc_email'] ?? '');
$bcc_email     = trim($data['bcc_email'] ?? '');
$subject       = trim($data['subject'] ?? '');
$template_id   = intval($data['template_id'] ?? 0);

// VALIDATION
if(!$alert_name || !$form_name || !$from_email || !$to_email || !$subject || !$template_id){
    echo json_encode(["status"=>"error","message"=>"All required fields must be provided"]);
    exit;
}

// INSERT
$sql = "INSERT INTO email_alerts 
(alert_name, description, form_name, from_email, to_email, cc_email, bcc_email, subject, template_id) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssssi", $alert_name, $description, $form_name, $from_email, $to_email, $cc_email, $bcc_email, $subject, $template_id);

if($stmt->execute()){
    echo json_encode([
        "status"=>"success",
        "message"=>"Email alert added successfully",
        "alert_id"=>$stmt->insert_id
    ]);
}else{
    echo json_encode(["status"=>"error","message"=>"Insert failed: ".$stmt->error]);
}

$stmt->close();
$conn->close();
?>
