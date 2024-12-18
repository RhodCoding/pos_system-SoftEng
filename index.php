<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect based on role
    if ($_SESSION['role'] == 'admin') {
        redirect('admin/index.php');
    } else {
        redirect('pos/index.php');
    }
} else {
    // Not logged in, redirect to login page
    redirect('login.php');
}
?>
