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
   UPDATE DESIGNATION (POST)
   =================================================== */
if ($method === "POST") {

    $data = json_decode(file_get_contents("php://input"), true);

    // Inputs
    $id = intval($_GET['id'] ?? $data['id'] ?? 0);
    $designation_name = trim($data['designation_name'] ?? '');
    $mail_alias = trim($data['mail_alias'] ?? '');
    $stream_id = intval($data['stream_id'] ?? 0);

    // Logged-in user ID
    $login_id = $_SESSION['emp_id'] ?? 1; // fallback for testing

    if ($id <= 0 || $designation_name === '') {
        echo json_encode([
            "status" => "error",
            "message" => "Designation ID and name are required"
        ]);
        exit;
    }

    // Get username from employee_login table
    $sql_name = "SELECT username FROM employee_login WHERE id = ?";
    $stmt_name = $conn->prepare($sql_name);
    $stmt_name->bind_param("i", $login_id);
    $stmt_name->execute();
    $result_name = $stmt_name->get_result();

    $modified_by = ($result_name && $result_name->num_rows > 0) ? 
                    $result_name->fetch_assoc()['username'] : 'Unknown';
    $stmt_name->close();

    // Update designation including login_id
    $stmt = $conn->prepare("
        UPDATE designation SET
            designation_name = ?,
            mail_alias = ?,
            stream_id = ?,
            modified_by = ?,
            login_id = ?,
            modified_time = NOW()
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssisii",
        $designation_name,
        $mail_alias,
        $stream_id,
        $modified_by,
        $login_id,
        $id
    );

    if ($stmt->execute()) {

        $stmt->close();

        // Fetch updated record
        $fetch = $conn->prepare("SELECT * FROM designation WHERE id = ?");
        $fetch->bind_param("i", $id);
        $fetch->execute();

        echo json_encode([
            "status" => "success",
            "message" => "Designation updated successfully",
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
   FETCH DESIGNATION (GET)
   =================================================== */
if ($method === "GET") {

    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid designation ID"
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT
            id,
            designation_name,
            mail_alias,
            stream_id,
            login_id,
            added_by,
            added_time,
            modified_by,
            modified_time
        FROM designation
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
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
            "message" => "Designation not found"
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
?>
