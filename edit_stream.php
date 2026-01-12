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
   UPDATE STREAM (POST)
   =================================================== */
if ($method === "POST") {

    $data = json_decode(file_get_contents("php://input"), true);

    // Inputs
    $stream_id   = intval($_GET['stream_id'] ?? $data['stream_id'] ?? 0);
    $name        = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $designation_id = trim($data['designation_id'] ?? '');

    // Logged-in user info
    $login_id = $_SESSION['emp_id'] ?? 0;

    if ($stream_id <= 0 || $name === '' || $designation_id === '') {
        echo json_encode([
            "status" => "error",
            "message" => "Stream ID, name and designation are required"
        ]);
        exit;
    }

    /* ===== Get username from employee_login table ===== */
    $stmt_name = $conn->prepare("SELECT username FROM employee_login WHERE id = ?");
    $stmt_name->bind_param("i", $login_id);
    $stmt_name->execute();
    $result_name = $stmt_name->get_result();
    $modified_by = ($result_name && $result_name->num_rows > 0) 
                   ? $result_name->fetch_assoc()['username'] 
                   : '';
    $stmt_name->close();

    /* ================= UPDATE QUERY ================= */
    $stmt = $conn->prepare("
        UPDATE streams SET
            name = ?,
            description = ?,
            designation_id = ?,
            login_id = ?,
            modified_by = ?,
            modified_time = NOW()
        WHERE stream_id = ?
    ");

    $stmt->bind_param(
        "ssissi",
        $name,
        $description,
        $designation_id,
        $login_id,
        $modified_by,
        $stream_id
    );

    if ($stmt->execute()) {
        $stmt->close();

        // Fetch updated record
        $fetch = $conn->prepare("SELECT * FROM streams WHERE stream_id = ?");
        $fetch->bind_param("i", $stream_id);
        $fetch->execute();
        $updated_stream = $fetch->get_result()->fetch_assoc();
        $fetch->close();

        echo json_encode([
            "status" => "success",
            "message" => "Stream updated successfully",
            "data" => $updated_stream
        ]);
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
   FETCH STREAM (GET)
   =================================================== */
if ($method === "GET") {

    $stream_id = intval($_GET['stream_id'] ?? 0);

    if ($stream_id <= 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid stream ID"
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT
            stream_id,
            name,
            description,
            designation_id,
            login_id,
            added_by,
            added_time,
            modified_by,
            modified_time
        FROM streams
        WHERE stream_id = ?
    ");

    $stmt->bind_param("i", $stream_id);
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
            "message" => "Stream not found"
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
