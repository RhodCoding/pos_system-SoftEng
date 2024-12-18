<?php
// includes/config.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bakery_pos');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set timezone
date_default_timezone_set('Your/Timezone');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global functions
function clean($string) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($string));
}

function redirect($location) {
    header("Location: $location");
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Error & Success messages
function showMessage($message, $type = 'success') {
    return "<div class='alert alert-{$type}'>{$message}</div>";
}
?>
