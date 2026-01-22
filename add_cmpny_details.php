<?php
header("Content-Type: application/json");
include "db.php"; // Database connection


// 🔹 Get POST data (JSON)
$data = json_decode(file_get_contents("php://input"), true);
// Assign variables from JSON
$name            = trim($data['name'] ?? '');
$logo            = trim($data['logo'] ?? ''); // URL or filename
$address         = trim($data['address'] ?? '');
$contact_person  = trim($data['contact_person'] ?? '');
$contact_email   = trim($data['contact_email'] ?? '');
$contact_phone   = trim($data['contact_phone'] ?? '');
$company_website = trim($data['company_website'] ?? '');
$timezone        = trim($data['timezone'] ?? '');

// ✅ Validation
if (!$name || !$contact_email) {
    echo json_encode([
        "status" => "error",
        "message" => "Company name and contact email are required"
    ]);
    exit;
}

// 🔹 Insert into company table
$sql = "INSERT INTO company_details (
            name, logo, address, contact_person, contact_email, contact_phone, company_website, timezone, updated_at
        ) VALUES (
            '$name', '$logo', '$address', '$contact_person', '$contact_email', '$contact_phone', '$company_website', '$timezone', NOW()
        )";

if (mysqli_query($conn, $sql)) {
    $company_id = mysqli_insert_id($conn);
    echo json_encode([
        "status" => "success",
        "message" => "Company added successfully",
        "company_id" => $company_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to add company: " . mysqli_error($conn)
    ]);
}
?>
