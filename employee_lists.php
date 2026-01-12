<?php
header("Content-Type: application/json");
include "db.php"; // Database connection

// 🔹 Fetch all employees
$sql = "SELECT 
    emp_id,
    first_name,
    last_name,
    email_id,
    department,
    designation,
    photo,
    date_of_joining,
    seating_location AS work_location,
    work_phone,
    extension,
    other_email,
    reporting_to,
    birthdate,
    mobile_phone,
    address_tags AS address,
    employee_status,
    employee_type,
    source_of_hire,
    marital_status,
    role AS employee_role,
    about_me,
    nick_name,
    job_description,
    gender,
    date_of_exit,
    askme_expertise,
    experience,
    age,
    visa_type AS type_of_visa,
    onboarding_status,
    stream,
    total_experience
FROM employee_registration
ORDER BY emp_id ASC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch employees: " . mysqli_error($conn)
    ]);
    exit;
}

$employees = [];

while ($row = mysqli_fetch_assoc($result)) {
    $employees[] = $row;
}

echo json_encode([
    "status" => "success",
    "count" => count($employees),
    "employees" => $employees
]);
?>
