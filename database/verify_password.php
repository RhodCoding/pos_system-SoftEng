<?php
require_once __DIR__ . '/../classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Get admin user details
    $query = "SELECT username, password FROM users WHERE username = 'admin'";
    $result = $db->query($query);
    
    if ($result && $user = $result->fetch_assoc()) {
        echo "Admin user found:\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Password hash: " . $user['password'] . "\n";
        
        // Verify password
        $testPassword = 'admin123';
        $isValid = password_verify($testPassword, $user['password']);
        echo "\nPassword verification test:\n";
        echo "Test password: " . $testPassword . "\n";
        echo "Is valid: " . ($isValid ? 'Yes' : 'No') . "\n";
        
        // Generate new hash for comparison
        $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
        echo "\nNew hash generated: " . $newHash . "\n";
    } else {
        echo "Admin user not found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
