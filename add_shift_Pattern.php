<?php
header("Content-Type: application/json");

// Include your DB connection
include "db.php"; // $conn from db.php

// Read raw JSON input
$data = json_decode(file_get_contents("php://input"), true);

$pattern_name = trim($data['pattern_name'] ?? '');
$pattern_type = $data['pattern_type'] ?? 'Weekly';
$repeat_every = intval($data['repeat_every'] ?? 1);

// Validate
if (empty($pattern_name)) {
    echo json_encode(['status'=>'error','message'=>'Pattern Name is required']);
    exit;
}

// Insert into shift_patterns
$sql = "INSERT INTO shift_patterns (pattern_name, pattern_type, repeat_every) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $pattern_name, $pattern_type, $repeat_every);

if ($stmt->execute()) {
    $pattern_id = $stmt->insert_id;
    echo json_encode([
        'status'=>'success',
        'message'=>'Shift pattern added successfully',
        'pattern_id'=>$pattern_id
    ]);
} else {
    echo json_encode([
        'status'=>'error',
        'message'=>'Insert Failed: '.$stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
