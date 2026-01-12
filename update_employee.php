<?php
header("Content-Type: application/json");
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json");
include "db.php"; // Your mysqli connection

// 🔹 Get Authorization header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    echo json_encode(["status"=>"error","message"=>"Token missing"]);
    exit;
}
$jwt = $matches[1];

// 🔹 Verify JWT
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

// 🔹 Get POST data (JSON)
$data = json_decode(file_get_contents("php://input"), true);

$emp_id = intval($data['emp_id'] ?? 0);
if (!$emp_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Employee ID is required"
    ]);
    exit;
}

// Assign variables (do NOT update emp_id, id, login_type, email_id, onboarding_status)
$first_name  = trim($data['first_name'] ?? '');
$last_name   = trim($data['last_name'] ?? '');
$contact     = trim($data['contact'] ?? '');
$nick_name   = trim($data['nick_name'] ?? '');
$department  = trim($data['department'] ?? '');
$designation = trim($data['designation'] ?? '');
$reporting_to= trim($data['reporting_to'] ?? '');
$source_of_hire = trim($data['source_of_hire'] ?? '');
$seating_location = trim($data['seating_location'] ?? '');
$location_title   = trim($data['location_title'] ?? '');
$stream           = trim($data['stream'] ?? '');
$date_of_joining  = trim($data['date_of_joining'] ?? '');
$employee_status  = trim($data['employee_status'] ?? '');
$employee_type    = trim($data['employee_type'] ?? '');
$work_phone       = trim($data['work_phone'] ?? '');
$extension        = trim($data['extension'] ?? '');
$role             = trim($data['role'] ?? '');
$total_experience = trim($data['total_experience'] ?? '');
$mobile_phone     = trim($data['mobile_phone'] ?? '');
$other_email      = trim($data['other_email'] ?? '');
$birthdate        = trim($data['birthdate'] ?? '');
$marital_status   = trim($data['marital_status'] ?? '');
$age              = intval($data['age'] ?? 0);
$visa_type        = trim($data['visa_type'] ?? '');
$address_tags     = trim($data['address_tags'] ?? '');
$password         = trim($data['password'] ?? '');
$gender           = trim($data['gender'] ?? '');
$experience       = trim($data['experience'] ?? '');
$photo            = trim($data['photo'] ?? '');
$job_description  = trim($data['job_description'] ?? '');
$askme_expertise  = trim($data['askme_expertise'] ?? '');
$passport_expiry_date = trim($data['passport_expiry_date'] ?? '');
$visa_expiry_date     = trim($data['visa_expiry_date'] ?? '');
$emergency_contact_name_home_town = trim($data['emergency_contact_name_home_town'] ?? '');
$emergency_contact_relation_home_town = trim($data['emergency_contact_relation_home_town'] ?? '');
$emergency_contact_no_home_town  = trim($data['emergency_contact_no_home_town'] ?? '');
$emergency_contact_name_uae      = trim($data['emergency_contact_name_uae'] ?? '');
$emergency_contact_relationship_uae = trim($data['emergency_contact_relationship_uae'] ?? '');
$emergency_contact_no_uae        = trim($data['emergency_contact_no_uae'] ?? '');
$about_me       = trim($data['about_me'] ?? '');
$date_of_exit   = trim($data['date_of_exit'] ?? '');

// 🔹 Update employee_registration
$sql_update = "UPDATE employee_registration SET
    first_name='$first_name',
    last_name='$last_name',
    contact='$contact',
    nick_name='$nick_name',
    department='$department',
    designation='$designation',
    reporting_to='$reporting_to',
    source_of_hire='$source_of_hire',
    seating_location='$seating_location',
    location_title='$location_title',
    stream='$stream',
    date_of_joining='$date_of_joining',
    employee_status='$employee_status',
    employee_type='$employee_type',
    work_phone='$work_phone',
    extension='$extension',
    role='$role',
    total_experience='$total_experience',
    mobile_phone='$mobile_phone',
    other_email='$other_email',
    birthdate='$birthdate',
    marital_status='$marital_status',
    age=$age,
    visa_type='$visa_type',
    address_tags='$address_tags',
    updated_at=NOW(),
    password='$password',
    gender='$gender',
    experience='$experience',
    photo='$photo',
    job_description='$job_description',
    askme_expertise='$askme_expertise',
    passport_expiry_date='$passport_expiry_date',
    visa_expiry_date='$visa_expiry_date',
    emergency_contact_name_home_town='$emergency_contact_name_home_town',
    emergency_contact_relation_home_town='$emergency_contact_relation_home_town',
    emergency_contact_no_home_town='$emergency_contact_no_home_town',
    emergency_contact_name_uae='$emergency_contact_name_uae',
    emergency_contact_relationship_uae='$emergency_contact_relationship_uae',
    emergency_contact_no_uae='$emergency_contact_no_uae',
    about_me='$about_me',
    date_of_exit='$date_of_exit'
WHERE emp_id=$emp_id";

if (mysqli_query($conn, $sql_update)) {
    echo json_encode([
        "status" => "success",
        "message" => "Employee updated successfully",
        "emp_id" => $emp_id
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to update employee: " . mysqli_error($conn)
    ]);
}
?>
