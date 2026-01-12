<?php
header("Content-Type: application/json");
include "db.php"; // Database connection

// 🔹 Fetch all companies (excluding company_id)
$sql = "SELECT name, logo, address, contact_person, contact_email, contact_phone, company_website, timezone, updated_at
        FROM company_details
        ORDER BY name ASC";

$result = mysqli_query($conn, $sql);

if ($result) {
    $companies = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $companies[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "message" => "Companies fetched successfully",
        "data" => $companies
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch companies: " . mysqli_error($conn)
    ]);
}
?>
