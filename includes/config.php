<?php
// Check if the session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start the session only if it hasn't been started yet
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Your database username
define('DB_PASS', '');            // Your database password
define('DB_NAME', 'pos_system');  // Your database name

// Base URL - Change this to your project URL
define('BASE_URL', 'http://localhost/pos_system');

// Connect to database
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set character set
mysqli_set_charset($conn, "utf8");

// Set timezone
date_default_timezone_set('Asia/Manila');  // Change this to your timezone
 