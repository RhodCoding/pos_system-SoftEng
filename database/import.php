<?php
require_once __DIR__ . '/../classes/Database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    
    // Split into individual queries
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    // Execute each query
    foreach ($queries as $query) {
        if (!empty($query)) {
            if (!$db->query($query)) {
                throw new Exception("Error executing query: " . $db->error);
            }
        }
    }
    
    echo "Database schema imported successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
