<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Create database if not exists
    $temp_conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($temp_conn->connect_error) {
        throw new Exception("Connection failed: " . $temp_conn->connect_error);
    }

    $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
    if (!$temp_conn->query($sql)) {
        throw new Exception("Error creating database: " . $temp_conn->error);
    }
    $temp_conn->close();

    // Connect to the database and execute schema
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $schema = file_get_contents(__DIR__ . '/schema.sql');
    $queries = explode(';', $schema);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            if (!$conn->query($query)) {
                throw new Exception("Error executing query: " . $conn->error);
            }
        }
    }

    echo "Database initialized successfully!\n";
    $conn->close();

} catch (Exception $e) {
    die("Database initialization failed: " . $e->getMessage() . "\n");
}
