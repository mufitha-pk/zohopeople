<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json");
include "db.php"; // mysqli connection

/* ================= JWT AUTH ================= */
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    echo json_encode(["status"=>"error","message"=>"Token missing"]);
    exit;
}
$jwt = $matches[1];

$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    if ($decoded->employee_type !== 'Admin') {
        echo json_encode(["status"=>"error","message"=>"Only admin can register employees"]);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(["status"=>"error","message"=>"Invalid token: ".$e->getMessage()]);
    exit;
}

/* ================= GET POST DATA ================= */
$data = json_decode(file_get_contents("php://input"), true);

$first_name    = trim($data['first_name'] ?? '');
$last_name     = trim($data['last_name'] ?? '');
$email_id      = trim($data['email_id'] ?? '');
$password      = trim($data['password'] ?? ''); // RAW password, no hashing
$username      = trim($data['username'] ?? '');

// ✅ Validation
if (!$first_name || !$email_id || !$password) {
    echo json_encode(["status"=>"error","message"=>"First name, email, and password are required"]);
    exit;
}

// 🔹 Auto-fill username if not provided
if (!$username) {
    $username = $first_name;
}

// 🔹 Ensure username is unique
$base_username = $username;
$counter = 1;
while (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM employee_login WHERE username='$username'")) > 0) {
    $username = $base_username . $counter; // e.g., John1, John2
    $counter++;
}

// 🔹 Check duplicate email
$check = mysqli_query($conn, "SELECT id FROM employee_login WHERE email_id='$email_id'");
if (mysqli_num_rows($check) > 0) {
    echo json_encode(["status"=>"error","message"=>"Email already exists"]);
    exit;
}

/* ================= INSERT INTO employee_login ================= */
$employee_type = 'Employee';
$employee_status = trim($data['employee_status'] ?? 'active');

$sql_login = "INSERT INTO employee_login (username, email_id, password, employee_type, employee_status)
              VALUES ('$username', '$email_id', '$password', '$employee_type', '$employee_status')";
if (!mysqli_query($conn, $sql_login)) {
    echo json_encode(["status"=>"error","message"=>"Failed to create login: ".mysqli_error($conn)]);
    exit;
}

$login_id = mysqli_insert_id($conn);

/* ================= INSERT INTO employee_registration ================= */
// Optional fields
$job_id             = trim($data['job_id'] ?? '');
$nick_name          = trim($data['nick_name'] ?? '');
$onboarding_status  = trim($data['onboarding_status'] ?? '');
$department         = trim($data['department'] ?? '');
$designation        = trim($data['designation'] ?? '');
$reporting_to       = trim($data['reporting_to'] ?? '');
$source_of_hire     = trim($data['source_of_hire'] ?? '');
$seating_location   = trim($data['seating_location'] ?? '');
$location_title     = trim($data['location_title'] ?? '');
$stream             = trim($data['stream'] ?? '');
$date_of_joining    = trim($data['date_of_joining'] ?? '');
$work_phone         = trim($data['work_phone'] ?? '');
$extension          = trim($data['extension'] ?? '');
$role               = trim($data['role'] ?? '');
$total_experience   = trim($data['total_experience'] ?? '');
$mobile_phone       = trim($data['mobile_phone'] ?? '');
$other_email        = trim($data['other_email'] ?? '');
$birthdate          = trim($data['birthdate'] ?? '');
$marital_status     = trim($data['marital_status'] ?? '');
$age                = intval($data['age'] ?? 0);
$visa_type          = trim($data['visa_type'] ?? '');
$address_tags       = trim($data['address_tags'] ?? '');
$gender             = trim($data['gender'] ?? '');
$experience         = trim($data['experience'] ?? '');
$photo              = trim($data['photo'] ?? '');
$job_description    = trim($data['job_description'] ?? '');
$employee_type      = trim($data['employee_type'] ?? 'Employee');
$login_type         = 'Employee';

$sql_employee = "INSERT INTO employee_registration (
    job_id, first_name, last_name, contact, email_id, nick_name, onboarding_status, department,
    designation, reporting_to, source_of_hire, seating_location, location_title, stream,
    date_of_joining, employee_status, employee_type, work_phone, extension, role, total_experience,
    mobile_phone, other_email, birthdate, marital_status, age, visa_type, address_tags, created_at,
    updated_at, password, login_type, emp_id, gender, experience, photo, job_description
) VALUES (
    '$job_id', '$first_name', '$last_name', '".trim($data['contact']??'')."', '$email_id', '$nick_name', '$onboarding_status', '$department',
    '$designation', '$reporting_to', '$source_of_hire', '$seating_location', '$location_title', '$stream',
    '$date_of_joining', '$employee_status', '$employee_type', '$work_phone', '$extension', '$role', '$total_experience',
    '$mobile_phone', '$other_email', '$birthdate', '$marital_status', $age, '$visa_type', '$address_tags', NOW(),
    NOW(), '$password', '$login_type', $login_id, '$gender', '$experience', '$photo', '$job_description'
)";

if (mysqli_query($conn, $sql_employee)) {
    echo json_encode([
        "status"=>"success",
        "message"=>"Employee registered successfully",
        "emp_id"=>$login_id,
        "username"=>$username
    ]);
} else {
    echo json_encode(["status"=>"error","message"=>"Failed to register: ".mysqli_error($conn)]);
}
?>
