<?php
// Check if the session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start the session only if it hasn't been started yet
}

error_reporting(E_ALL); // Report all types of errors
ini_set('display_errors', 1); // Display errors on the screen
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Admin Dashboard'; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="#">POS System</a>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item"><a class="nav-link" href="/pos_system/admin/dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/pos_system/admin/users/index.php">Users</a></li>
            <li class="nav-item"><a class="nav-link" href="/pos_system/admin/products/index.php">Products</a></li>
            <li class="nav-item"><a class="nav-link" href="/pos_system/admin/categories/index.php">Categories</a></li>
            <li class="nav-item"><a class="nav-link" href="/pos_system/admin/stock/index.php">Stock</a></li>
            <li class="nav-item"><a class="nav-link" href="/pos_system/admin/settings/index.php">Settings</a></li>
            <li class="nav-item"><a class="nav-link" href="/pos_system/admin/reports/index.php">Reports</a></li>
            <li class="nav-item"><a class="nav-link" href="/pos_system/logout.php">Logout</a></li>
        </ul>
    </div>
</nav>
<div class="container">