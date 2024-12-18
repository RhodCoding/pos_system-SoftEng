<?php
require_once __DIR__ . '/../config/database.php';

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'employee') NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($db->query($sql)) {
    echo "Users table ready\n";
} else {
    echo "Error creating users table: " . $db->error . "\n";
    exit;
}

// Create employee account
$username = 'employee1';
$password = password_hash('employee123', PASSWORD_DEFAULT);
$name = 'Sample Employee';
$role = 'employee';

// Check if user already exists
$stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Employee account already exists\n";
} else {
    // Create new employee
    $sql = "INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssss", $username, $password, $name, $role);
    
    if ($stmt->execute()) {
        echo "Employee account created successfully!\n";
        echo "Username: employee1\n";
        echo "Password: employee123\n";
    } else {
        echo "Error creating employee: " . $stmt->error . "\n";
    }
}

$db->close();
