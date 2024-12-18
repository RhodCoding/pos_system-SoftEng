<?php
require_once 'classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "Database connection successful!\n";
    
    // Test users table
    $result = $db->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        echo "Users table exists!\n";
        
        // Show table structure
        $result = $db->query("DESCRIBE users");
        echo "\nUsers table structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
        }
        
        // Count users
        $result = $db->query("SELECT COUNT(*) as count FROM users");
        $count = $result->fetch_assoc()['count'];
        echo "\nTotal users in database: {$count}\n";
    } else {
        echo "Users table does not exist!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
