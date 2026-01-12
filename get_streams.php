<?php
// ================= CORS & JSON =================
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

session_start();
include "db.php";
// if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
//     echo json_encode([
//         "status" => "error",
//         "message" => "User not logged in"
//     ]);
//     exit;
// }
echo json_encode($_SESSION);
// ================= GET STREAM ID (optional) =================
$stream_id = intval($_GET['stream_id'] ?? 0);

if ($stream_id > 0) {
    // Fetch a single stream
    $stmt = $conn->prepare("
        SELECT s.stream_id,
               s.name,
               s.description,
               s.designation_id,
               COALESCE(d.designation_name, 'No Designation') AS designation,
               s.login_id,
               s.added_by,
               s.added_time,
               s.modified_by,
               s.modified_time
        FROM streams s
        LEFT JOIN designation d ON s.designation_id = d.id
        WHERE s.stream_id = ?
    ");
    $stmt->bind_param("i", $stream_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $stream = $result->fetch_assoc();
        echo json_encode([
            "status" => "success",
            "stream" => $stream
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Stream ID $stream_id not found"
        ]);
    }
    $stmt->close();

} else {
    // Fetch all streams
    $sql = "
        SELECT s.stream_id,
               s.name,
               s.description,
               s.designation_id,
               COALESCE(d.designation_name, 'No Designation') AS designation,
               s.login_id,
               s.added_by,
               s.added_time,
               s.modified_by,
               s.modified_time
        FROM streams s
        LEFT JOIN designation d ON s.designation_id = d.id
        ORDER BY s.added_time DESC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        echo json_encode([
            "status" => "error",
            "message" => $conn->error
        ]);
        exit;
    }

    $streams = [];
    while ($row = $result->fetch_assoc()) {
        $streams[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "streams" => $streams
    ]);
}

$conn->close();
?>
