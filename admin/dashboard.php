<?php
session_start(); // Start the session
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    // Debugging: Check session variables
    error_log("User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set'));
    error_log("User Role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'Not set'));
    
    redirect('../login.php');
    
}

// Your dashboard content goes here
$page_title = 'Admin Dashboard';
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <h1>Welcome to the Admin Dashboard</h1>
    <p>You are logged in as: <?php echo $_SESSION['full_name']; ?></p>
    <!-- Add more dashboard content here -->
</div>

<?php include_once '../includes/footer.php'; ?>



