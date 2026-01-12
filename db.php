<?php
// Database configuration
$host = "localhost";        // Usually localhost
$user = "root";             // Your DB username
$password = "";             // Your DB password (default XAMPP is empty)
$database = "hrconnect";  
// Create connection
$conn = mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$conn) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . mysqli_connect_error()
    ]));
}

// Optional: set charset to utf8
mysqli_set_charset($conn, "utf8");
?>
