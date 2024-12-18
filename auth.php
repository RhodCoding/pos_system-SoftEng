<?php
session_start();
require_once __DIR__ . '/classes/User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $userModel = new User();
    if ($user = $userModel->authenticate($username, $password)) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: employee/pos.php');
        }
        exit();
    } else {
        $_SESSION['login_error'] = 'Invalid username or password';
        header('Location: index.php');
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
