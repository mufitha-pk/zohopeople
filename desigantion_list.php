<?php
header("Content-Type: application/json");
include "db.php";

session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in"
    ]);
    exit;
}
echo json_encode($_SESSION);

$search = trim($_GET['search'] ?? '');
$searchLike = "%$search%";

$sql = "
    SELECT
        d.id,
        d.designation_name,
        d.mail_alias,
        d.stream_id,
        s.name AS stream_name,   
        d.login_id,
        el.username AS login_username,
        d.added_by,
        d.added_time,
        d.modified_by,
        d.modified_time
    FROM designation d
    LEFT JOIN employee_login el ON d.login_id = el.id
    LEFT JOIN streams s ON d.stream_id = s.stream_id
    WHERE d.designation_name LIKE ?
    ORDER BY d.id DESC
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $searchLike);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

echo json_encode([
    "status" => "success",
    "count" => count($data),
    "data" => $data
]);
?>
