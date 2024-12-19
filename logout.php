<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Start the session
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
redirect('login.php');
