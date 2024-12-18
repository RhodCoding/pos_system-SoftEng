<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password Hash: " . $hash . "\n";

echo "\nSQL Query to run:\n";
echo "DELETE FROM users WHERE username = 'admin';\n";
echo "INSERT INTO users (username, password, full_name, role) VALUES \n";
echo "('admin', '" . $hash . "', 'Administrator', 'admin');\n";
?>
