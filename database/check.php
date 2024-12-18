<?php
require_once __DIR__ . '/../classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if tables exist
    $tables = ['users', 'categories', 'products', 'orders', 'order_items', 'rate_limits'];
    $results = [];
    
    foreach ($tables as $table) {
        $query = "SELECT COUNT(*) as count FROM $table";
        $result = $db->query($query);
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            $results[$table] = $count;
            echo "$table table exists with $count records\n";
        } else {
            echo "Error checking $table: " . $db->error . "\n";
        }
    }
    
    // Check admin user
    $query = "SELECT username, role FROM users WHERE username = 'admin'";
    $result = $db->query($query);
    if ($result && $user = $result->fetch_assoc()) {
        echo "\nAdmin user exists with role: " . $user['role'] . "\n";
    } else {
        echo "\nAdmin user not found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
