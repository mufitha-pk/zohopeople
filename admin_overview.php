<?php
include 'db.php';
session_start();

header("Content-Type: application/json");

// ================= AUTH CHECK =================
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

// ================= ROLE CHECK =================
if (!in_array($_SESSION['employee_type'], ['Admin', 'Super Admin'])) {
    echo json_encode(["error" => "Admin only"]);
    exit;
}

$empId = $_SESSION['emp_id'];

// ---------------------------
// Employee
$emp_sql = "SELECT emp_id, CONCAT(first_name,' ',last_name) AS name, designation, photo
            FROM employee_registration WHERE emp_id = ?";
$stmt = $conn->prepare($emp_sql);
$stmt->bind_param("i", $empId);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($employee && !$employee['photo']) {
    $employee['photo'] = "/images/default_user.png";
}

// ---------------------------
// Manager
$manager_sql = "SELECT CONCAT(first_name,' ',last_name) AS name, photo
                FROM employee_registration
                WHERE emp_id = (
                    SELECT reporting_to FROM employee_registration WHERE emp_id = ?
                )";
$stmt = $conn->prepare($manager_sql);
$stmt->bind_param("i", $empId);
$stmt->execute();
$manager = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ---------------------------
// Reportees
$reportees_sql = "SELECT emp_id, CONCAT(first_name,' ',last_name) AS name, designation, photo
                  FROM employee_registration WHERE reporting_to = ?";
$stmt = $conn->prepare($reportees_sql);
$stmt->bind_param("i", $empId);
$stmt->execute();
$reportees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

echo json_encode([
    "status" => "success",
    "employee" => $employee,
    "manager" => $manager,
    "reportees" => $reportees
]);
