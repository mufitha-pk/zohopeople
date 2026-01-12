<?php
// ================= CORS =================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");

// ================= SESSION =================
session_start();

// ================= CHECK LOGIN =================
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in"
    ]);
    exit;
}

// ================= DB =================
include "db.php";

// ================= FETCH GROUPS =================
$sql = "SELECT * FROM groups ORDER BY group_id DESC";
$result = $conn->query($sql);

$groups = [];

while ($group = $result->fetch_assoc()) {

    // ---------- Decode JSON ----------
    $admin_ids  = json_decode($group['administrators_id'], true) ?? [];
    $member_ids = json_decode($group['members_id'], true) ?? [];

    // ---------- Fetch Admin Names ----------
    $admins = [];
    if (!empty($admin_ids)) {
        $ids = implode(',', array_map('intval', $admin_ids));
        $adminQuery = "SELECT emp_id, first_name FROM employee_registration WHERE emp_id IN ($ids)";
        $adminResult = $conn->query($adminQuery);

        while ($row = $adminResult->fetch_assoc()) {
            $admins[] = $row;
        }
    }

    // ---------- Fetch Member Names ----------
    $members = [];
    if (!empty($member_ids)) {
        $ids = implode(',', array_map('intval', $member_ids));
        $memberQuery = "SELECT emp_id, first_name FROM employee_registration WHERE emp_id IN ($ids)";
        $memberResult = $conn->query($memberQuery);

        while ($row = $memberResult->fetch_assoc()) {
            $members[] = $row;
        }
    }

    // ---------- Final Group Object ----------
    $groups[] = [
        "group_id" => $group['group_id'],
        "group_name" => $group['group_name'],
        "description" => $group['description'],
        "group_email" => $group['group_email'],
        "notify_new_members" => (int)$group['notify_new_members'],
        "administrators" => $admins,
        "members" => $members
    ];
}

echo json_encode([
    "status" => "success",
    "data" => $groups
]);

$conn->close();
