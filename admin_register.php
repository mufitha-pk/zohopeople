<?php
header("Content-Type: application/json");
include "db.php"; // Database connection

// 🔹 Get POST data (JSON)
$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');
$confirm_password = trim($data['confirm_password'] ?? '');

// ✅ Validation
if (!$name || !$email || !$password || !$confirm_password) {
    echo json_encode([
        "status" => "error",
        "message" => "All fields are required"
    ]);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode([
        "status" => "error",
        "message" => "Passwords do not match"
    ]);
    exit;
}

// 🔹 Check if email already exists in employee_login
$check_email = mysqli_query($conn, "SELECT id FROM employee_login WHERE email_id='$email'");
if (mysqli_num_rows($check_email) > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Email already exists"
    ]);
    exit;
}

// 🔹 Insert into employee_login (plain password for testing)
$employee_type = 'Admin';
$employee_status = 'active';

$sql_login = "INSERT INTO employee_login (email_id, password, employee_type, employee_status)
              VALUES ('$email', '$password', '$employee_type', '$employee_status')";

if (mysqli_query($conn, $sql_login)) {
    // Get the inserted employee_login ID
    $login_id = mysqli_insert_id($conn);

    // 🔹 Insert into admin_registration (with password stored if needed)
    $sql_admin = "INSERT INTO admin_registration (name, email, login_type, password, confirm_password, admin_id)
                  VALUES ('$name', '$email', 'Admin', '$password', '$confirm_password', $login_id)";

    if (mysqli_query($conn, $sql_admin)) {
        echo json_encode([
            "status" => "success",
            "message" => "Admin registered successfully",
            "admin_id" => $login_id,
            "password" => $password // Only for testing in Postman
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to insert into admin_registration: " . mysqli_error($conn)
        ]);
    }

} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to insert into employee_login: " . mysqli_error($conn)
    ]);
}
?>
