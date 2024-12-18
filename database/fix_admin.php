<?php
require_once __DIR__ . '/../classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Generate correct password hash
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Update admin password
    $query = "UPDATE users SET password = '$hash' WHERE username = 'admin'";
    if ($db->query($query)) {
        echo "Admin password updated successfully!\n";
        
        // Verify the update
        $query = "SELECT username, password FROM users WHERE username = 'admin'";
        $result = $db->query($query);
        if ($result && $user = $result->fetch_assoc()) {
            $isValid = password_verify($password, $user['password']);
            echo "Password verification: " . ($isValid ? 'Success' : 'Failed') . "\n";
        }
    } else {
        echo "Failed to update admin password\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
