<?php
header("Content-Type: application/json");
include "db.php";

/* ================= JWT AUTH ================= */
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret_key = "sDf8uY7hG5rT3kL1pQwE9zXcVbNm2aS4dF6gH7jK8lM9pQ0";

$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Token missing"]);
    exit;
}

$token = str_replace("Bearer ", "", $headers['Authorization']);
try {
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["status"=>"error","message"=>"Invalid token"]);
    exit;
}

if ($decoded->employee_type !== 'Admin') {
    http_response_code(403);
    echo json_encode(["status"=>"error","message"=>"Admin only"]);
    exit;
}

/* ============================================ */
$data = json_decode(file_get_contents("php://input"), true);

/* ---------- required ---------- */
if (empty($data['policy_type_name']) || empty($data['valid_from'])) {
    http_response_code(400);
    echo json_encode(["status"=>"error","message"=>"policy_type_name & valid_from required"]);
    exit;
}

$policy_type_name = strtolower($data['policy_type_name']);
$description = $data['description'] ?? null;

/* ================= STEP 1: Check / Insert leave_policy ================= */
$check_stmt = $conn->prepare("SELECT leave_policy_id FROM leave_policy WHERE policy_type_name = ? LIMIT 1");
$check_stmt->bind_param("s", $policy_type_name);
$check_stmt->execute();
$check_stmt->store_result();
$check_stmt->bind_result($policy_id);

if ($check_stmt->num_rows > 0) {
    $check_stmt->fetch(); // Policy exists, reuse policy_id
} else {
    $stmt1 = $conn->prepare("INSERT INTO leave_policy (policy_type_name, description) VALUES (?, ?)");
    $stmt1->bind_param("ss", $policy_type_name, $description);
    if (!$stmt1->execute()) {
        echo json_encode(["status"=>"error","message"=>$stmt1->error]);
        exit;
    }
    $policy_id = $stmt1->insert_id;
    $stmt1->close();
}
$check_stmt->close();

/* ================= STEP 2: Insert entitlement ================= */
if ($policy_type_name === 'fixed') {
    $fields = [
        'name','color','image','code','type','unit','valid_from','expires_on',
        'effective_value','effective_after_years','effective_from','entitlement_value',
        'entitlement_year_start','reset_frequency','entitlement_year_end','description',
        'applicable_to','exceptions','exceed_entitlement_rule','sandwich_leave_policy',
        'cannot_taken_along_with','allow_employee_view','balance_display','supporting_document_required',
        'document_threshold_days','allowed_full_day','allowed_half_day','allowed_quarter_day',
        'allowed_hourly','past_dates_allowed','past_dates','future_dates_allowed','future_days',
        'apply_in_advance','apply_in_advance_days','admin_only','min_leave_request','max_leave_request',
        'max_consecutive_days','min_gap_between_requests','max_requests_per_period','request_period',
        'allowed_on_days','allowed_on_weeks'
    ];
    $table = "fixed_entitlement";
} elseif ($policy_type_name === 'experience') {
    $fields = [
        'name','color','image','code','type','unit','description','valid_from','expires_on',
        'effective_after_no','effective_after_years','from_date_of_joining','leave_to_be_credited_days',
        'leave_to_be_credited_yearly','on_which_day','which_month','reset_yearly','on_date_of_month',
        'carry_forward','carry_forward_no','carry_forward_percentage','max_limit','unused_leave_no',
        'unused_leave_percentage','unused_max_limit','applicable_to','exceptions','exceed_entitlement_rule',
        'sandwich_leave_policy','cannot_taken_along_with','allow_employee_view','balance_display',
        'supporting_document_required','document_threshold_days','allowed_full_day','allowed_half_day',
        'allowed_quarter_day','allowed_hourly','past_dates_allowed','past_dates','future_dates_allowed',
        'future_days','apply_in_advance','apply_in_advance_days','admin_only','min_leave_request',
        'max_leave_request','max_consecutive_days','min_gap_between_requests','max_requests_per_period',
        'request_period','allowed_on_days','allowed_on_weeks'
    ];
    $table = "leave_policy_experience";
} else {
    echo json_encode(["status"=>"error","message"=>"Invalid policy_type_name"]);
    exit;
}

/* Prepare values */
$values = [];
foreach ($fields as $f) {
    if (isset($data[$f])) {
        $values[] = $data[$f];
    } else {
        // default values for numeric / boolean fields
        if (strpos($f,'_no')!==false || strpos($f,'_days')!==false || strpos($f,'_percentage')!==false || strpos($f,'max')!==false || strpos($f,'min')!==false) $values[] = 0;
        elseif ($f=='carry_forward' || $f=='supporting_document_required' || strpos($f,'allowed')!==false || strpos($f,'apply')!==false || strpos($f,'past')!==false || strpos($f,'future')!==false || $f=='admin_only') $values[] = 0;
        else $values[] = null;
    }
}

/* Insert entitlement */
$placeholders = implode(',', array_fill(0, count($fields)+1, '?')); // +1 for policy_id
$sql = "INSERT INTO $table (policy_id,".implode(',', $fields).") VALUES ($placeholders)";
$stmt = $conn->prepare($sql);
$types = str_repeat('s', count($fields)+1);
$stmt->bind_param($types, $policy_id, ...$values);

if (!$stmt->execute()) {
    echo json_encode(["status"=>"error","message"=>$stmt->error]);
    exit;
}
$stmt->close();

/* ================= SUCCESS ================= */
echo json_encode([
    "status" => "success",
    "policy_id" => $policy_id,
    "message" => ucfirst($policy_type_name)." leave type added successfully"
]);

$conn->close();
?>
