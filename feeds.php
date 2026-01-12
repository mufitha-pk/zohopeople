<?php
header("Content-Type: application/json");

require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

include "db.php"; // mysqli connection

/* ================= JWT AUTH ================= */
$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Token missing"]);
    exit;
}

$jwt = $matches[1];

try {
    $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
    if ($decoded->employee_type !== 'Admin') {
        http_response_code(403);
        echo json_encode(["status"=>"error","message"=>"Access denied, Admin only"]);
        exit;
    }
    $actor_id = $decoded->emp_id; // Admin who triggers
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Invalid or expired token"]);
    exit;
}

/* ================= Logging Setup ================= */
$log_dir  = __DIR__ . "/logs";
$log_file = $log_dir . "/birthday_run.log";

if (!file_exists($log_dir)) mkdir($log_dir, 0777, true);

function write_log($message){
    global $log_file;
    $date = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$date] $message\n", FILE_APPEND);
}

write_log("========== Script Started ==========");

/* ================= Fetch today's birthdays ================= */
$today = date('m-d');

$sql = "SELECT emp_id, first_name, last_name, email_id
        FROM employee_registration
        WHERE DATE_FORMAT(birthdate, '%m-%d') = ?
        AND employee_status='active'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0){
    write_log("No birthdays today");
    echo json_encode(["status"=>"success","message"=>"No birthdays today", "data"=>[]]);
    exit;
}

/* ================= Fetch Birthday Email Template ================= */
$alert_sql = "SELECT ea.id AS alert_id, ea.subject, ea.from_email, et.message, et.field_value
              FROM email_alerts ea
              JOIN email_templates et ON ea.template_id = et.id
              WHERE et.template_name = 'Birthday Wish' LIMIT 1";

$alert_res = $conn->query($alert_sql);
if($alert_res->num_rows === 0){
    write_log("Birthday email template not found");
    echo json_encode(["status"=>"error","message"=>"No birthday email template found"]);
    exit;
}

$alert = $alert_res->fetch_assoc();
$from_email = $alert['from_email'];

/* ================= Fetch all active employees ================= */
$all_emps_sql = "SELECT emp_id, first_name, last_name, email_id
                 FROM employee_registration
                 WHERE employee_status='active'";
$all_emps_result = $conn->query($all_emps_sql);
$all_emps = $all_emps_result->fetch_all(MYSQLI_ASSOC);

/* ================= Loop through birthday employees ================= */
$feeds_created = [];

while($birthday = $result->fetch_assoc()){
    $b_name = $birthday['first_name'] . " " . $birthday['last_name'];
    $b_email = $birthday['email_id'];
    $current_time = date('d F H:i A');

    $formatted_message = "
    Birthday wish from Kyoto Team<br><br>
    $current_time<br><br>
    From: $from_email<br><br>
    <b>HAPPY BIRTHDAY $b_name</b><br><br>
    &ldquo; KYOTO TEAM wishing you a day filled with happiness and a year filled with joy. &rdquo;<br><br>
    With regards,<br>
    HR team.
    ";

    foreach($all_emps as $emp){
        $emp_id = $emp['emp_id'];

        // Check if feed already exists today
        $check_sql = "SELECT id FROM feeds WHERE emp_id = ? AND DATE(created_at) = CURDATE() AND message LIKE ?";
        $check_stmt = $conn->prepare($check_sql);
        $like_message = "%$b_name%";
        $check_stmt->bind_param("is", $emp_id, $like_message);
        $check_stmt->execute();
        $check_stmt->store_result();

        if($check_stmt->num_rows == 0){
            $feed_sql = "INSERT INTO feeds (emp_id, actor_id, message, created_at)
                         VALUES (?, ?, ?, NOW())";
            $feed_stmt = $conn->prepare($feed_sql);
            $feed_stmt->bind_param("iis", $emp_id, $actor_id, $formatted_message);
            $feed_stmt->execute();
            $feed_stmt->close();

            write_log("Feed inserted for emp_id=$emp_id for birthday $b_name");

            // Collect for Postman response
            $feeds_created[] = [
                "emp_id"=>$emp_id,
                "birthday_person"=>$b_name,
                "message"=>$formatted_message,
                "from_email"=>$from_email,
                "time"=>$current_time
            ];
        }

        $check_stmt->close();
    }

    // Optional: Send email (commented out for local testing)
    // mail($b_email, $alert['subject'], $formatted_message, "From: $from_email\r\nContent-Type: text/html; charset=UTF-8");
}

write_log("========== Script Finished ==========\n");

echo json_encode([
    "status"=>"success",
    "message"=>"Birthday feeds processed",
    "total_feeds"=>count($feeds_created),
    "data"=>$feeds_created
]);

$conn->close();
?>
