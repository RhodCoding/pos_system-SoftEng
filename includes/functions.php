<?php

/**
 * Check if user is logged in
 * @return boolean
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * @return boolean
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

/**
 * Redirect to a URL
 * @param string $url
 */
function redirect($url) {
    header("Location: " . BASE_URL . "/" . $url);
    exit();
}

/**
 * Clean input data
 * @param string $data
 * @return string
 */
function clean($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

/**
 * Format currency
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return getSetting('currency_symbol', '₱') . number_format($amount, 2);
}

/**
 * Get setting value
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getSetting($key, $default = null) {
    global $conn;
    
    $query = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'];
    }
    
    return $default;
}

/**
 * Update setting value
 * @param string $key
 * @param mixed $value
 * @return boolean
 */
function updateSetting($key, $value) {
    global $conn;
    
    $query = "INSERT INTO settings (setting_key, setting_value) 
              VALUES (?, ?) 
              ON DUPLICATE KEY UPDATE setting_value = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $key, $value, $value);
    
    return mysqli_stmt_execute($stmt);
}
