<?php
header("Content-Type: application/json");
include "db.php";
session_start();

/* ================= AUTH CHECK ================= */
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in"
    ]);
    exit;
}

/* ================= REQUEST METHOD ================= */
$method = $_SERVER['REQUEST_METHOD'];

/* ===================================================
   UPDATE DEPARTMENT (POST)
   =================================================== */
if ($method === "POST") {

    $data = json_decode(file_get_contents("php://input"), true);

    // Inputs
    $department_id = intval($_GET['id'] ?? $data['department_id'] ?? 0);
    $department_name = trim($data['department_name'] ?? '');
    $department_head = trim($data['department_head'] ?? '');
    $mail_alias = trim($data['mail_alias'] ?? '');
    $parent_department_id = intval($data['parent_department_id'] ?? 0);

    // Logged-in user ID
    $login_id = $_SESSION['emp_id'];

    if ($department_id <= 0 || $department_name === '' || $department_head === '') {
        echo json_encode([
            "status" => "error",
            "message" => "Department ID, name and head are required"
        ]);
        exit;
    }

    /* ===== Get username from employee_login table ===== */
    $sql_name = "SELECT username FROM employee_login WHERE id = ?";
    $stmt_name = $conn->prepare($sql_name);
    $stmt_name->bind_param("i", $login_id);
    $stmt_name->execute();
    $result_name = $stmt_name->get_result();

    if ($result_name && $result_name->num_rows > 0) {
        $row = $result_name->fetch_assoc();
        $modified_by = $row['username'];
    } else {
        $modified_by = ''; // fallback
    }
    $stmt_name->close();

    /* ================= UPDATE QUERY ================= */
    $stmt = $conn->prepare("
        UPDATE departments SET
            department_name = ?,
            department_head = ?,
            mail_alias = ?,
            parent_department_id = ?,
            modified_by = ?,
            modified_time = NOW()
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sssisi",
        $department_name,
        $department_head,
        $mail_alias,
        $parent_department_id,
        $modified_by,
        $department_id
    );

    if ($stmt->execute()) {

        $stmt->close();

        // Fetch updated record
        $fetch = $conn->prepare("SELECT * FROM departments WHERE id = ?");
        $fetch->bind_param("i", $department_id);
        $fetch->execute();

        echo json_encode([
            "status" => "success",
            "message" => "Department updated successfully",
            "data" => $fetch->get_result()->fetch_assoc()
        ]);

        $fetch->close();

    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Update failed: " . $stmt->error
        ]);
    }

    $conn->close();
    exit;
}

/* ===================================================
   FETCH DEPARTMENT (GET)
   =================================================== */
if ($method === "GET") {

    $department_id = intval($_GET['id'] ?? 0);

    if ($department_id <= 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid department ID"
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT
            id,
            department_name,
            department_head,
            mail_alias,
            parent_department_id,
            added_by,
            added_time,
            modified_by,
            modified_time
        FROM departments
        WHERE id = ?
    ");

    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        echo json_encode([
            "status" => "success",
            "data" => $result->fetch_assoc()
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Department not found"
        ]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

/* ================= INVALID METHOD ================= */
echo json_encode([
    "status" => "error",
    "message" => "Invalid request method"
]);
$conn->close();
