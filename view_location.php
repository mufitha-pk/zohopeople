<?php
header("Content-Type: application/json");
include "db.php";

// 🔹 Get locations with associated users count
$sql = "
    SELECT 
        l.location_name,
        l.mail_alias,
        l.address,
        COUNT(e.id) AS associated_users
    FROM locations l
    LEFT JOIN employee_registration e 
        ON e.location_title = l.location_name
    GROUP BY l.location_name, l.mail_alias, l.address
    ORDER BY l.location_name ASC
";

$result = mysqli_query($conn, $sql);

$locations = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $locations[] = [
            "location_name"     => $row['location_name'],
            "associated_users"  => (int)$row['associated_users'],
            "email"             => $row['mail_alias'],
            "address"           => $row['address']
        ];
    }

    echo json_encode([
        "status" => "success",
        "total_count" => count($locations),
        "data" => $locations
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to fetch locations: " . mysqli_error($conn)
    ]);
}
?>
 