<?php
header("Content-Type: application/json");

include "db.php"; // mysqli connection
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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

    $admin_id = $decoded->emp_id; // Admin triggering this

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Invalid or expired token"]);
    exit;
}

/* ================= FETCH TODAY'S BIRTHDAYS ================= */
$today = date('m-d');

$sql = "SELECT emp_id, first_name, last_name, email_id 
        FROM employee_registration 
        WHERE DATE_FORMAT(birthdate, '%m-%d') = ? 
          AND employee_status='active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$birthday_emps = $stmt->get_result();

if ($birthday_emps->num_rows === 0) {
    echo json_encode(["status"=>"success","message"=>"No birthdays today."]);
    exit;
}

/* ================= FETCH ALL ACTIVE EMPLOYEES ================= */
$all_emps_sql = "SELECT emp_id, first_name, last_name, email_id 
                 FROM employee_registration 
                 WHERE employee_status='active'";
$all_emps_result = $conn->query($all_emps_sql);
$all_emps = $all_emps_result->fetch_all(MYSQLI_ASSOC);

/* ================= FETCH BIRTHDAY EMAIL ALERT ================= */
$alert_sql = "SELECT ea.id AS alert_id, ea.subject, ea.from_email, et.message, et.field_value
              FROM email_alerts ea
              JOIN email_templates et ON ea.template_id = et.id
              WHERE et.template_name = 'Birthday Wish' LIMIT 1";
$alert_res = $conn->query($alert_sql);

if ($alert_res->num_rows === 0) {
    echo json_encode(["status"=>"error","message"=>"No birthday email alert/template found."]);
    exit;
}

$alert = $alert_res->fetch_assoc();
$alert_id = $alert['alert_id'];

/* ================= LOOP THROUGH BIRTHDAY EMPLOYEES ================= */
while ($birthday = $birthday_emps->fetch_assoc()) {
    $b_name = $birthday['first_name'] . " " . $birthday['last_name'];
    $b_email = $birthday['email_id'];

    // Prepare personalized message
    $message = str_replace($alert['field_value'], $b_name, $alert['message']);

    // Broadcast feed to all employees
    foreach ($all_emps as $emp) {
        $emp_id = $emp['emp_id'];

        // Avoid duplicate feeds for the same day
        $check_sql = "SELECT id FROM feeds WHERE emp_id = ? AND DATE(created_at) = CURDATE() AND message LIKE ?";
        $check_stmt = $conn->prepare($check_sql);
        $like_message = "%".$b_name."%";
        $check_stmt->bind_param("is", $emp_id, $like_message);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows == 0) {
            // Insert feed
            $feed_sql = "INSERT INTO feeds (emp_id, actor_id, message, created_at)
                         VALUES (?, ?, ?, NOW())";
            $feed_stmt = $conn->prepare($feed_sql);
            $feed_stmt->bind_param("iis", $emp_id, $alert_id, $message);
            $feed_stmt->execute();
            $feed_stmt->close();
        }

        $check_stmt->close();
    }

    // Send email to birthday employee only
    $to = $b_email;
    $subject = $alert['subject'];
    $headers = "From: ".$alert['from_email']."\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    mail($to, $subject, nl2br($message), $headers);
}

echo json_encode([
    "status" => "success",
    "message" => "Birthday feeds created and emails sent successfully."
]);

$conn->close();
?>
