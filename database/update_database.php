<?php
require_once __DIR__ . '/../classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Add status column if it doesn't exist
    $addColumnQuery = "ALTER TABLE users
                      ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') 
                      NOT NULL DEFAULT 'active' AFTER role";
    
    if ($db->query($addColumnQuery)) {
        echo "Successfully added status column\n";
        
        // Update existing records
        $updateQuery = "UPDATE users SET status = 'active' WHERE status IS NULL";
        if ($db->query($updateQuery)) {
            echo "Successfully updated existing records\n";
        }
    }
    
    echo "Database update completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
