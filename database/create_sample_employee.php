<?php
require_once __DIR__ . '/../classes/User.php';

// Create sample employee
$user = new User();

$employeeData = [
    'username' => 'employee1',
    'password' => 'employee123',
    'name' => 'Sample Employee',
    'role' => 'employee'
];

try {
    $result = $user->create($employeeData);
    if ($result) {
        echo "Sample employee account created successfully!\n";
        echo "Username: employee1\n";
        echo "Password: employee123\n";
    } else {
        echo "Failed to create employee account.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
