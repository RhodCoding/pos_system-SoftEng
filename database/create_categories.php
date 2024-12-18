<?php
require_once __DIR__ . '/../config/database.php';

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Create categories table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($db->query($sql)) {
    echo "Categories table created successfully\n";
} else {
    echo "Error creating categories table: " . $db->error . "\n";
}

// Insert default categories if they don't exist
$defaultCategories = [
    ['name' => 'Bread', 'description' => 'Fresh baked breads'],
    ['name' => 'Pastries', 'description' => 'Sweet and savory pastries'],
    ['name' => 'Cakes', 'description' => 'Custom and ready-made cakes'],
    ['name' => 'Beverages', 'description' => 'Hot and cold drinks']
];

$stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");

foreach ($defaultCategories as $category) {
    // Check if category already exists
    $check = $db->query("SELECT id FROM categories WHERE name = '" . $db->real_escape_string($category['name']) . "'");
    if ($check->num_rows == 0) {
        $stmt->bind_param("ss", $category['name'], $category['description']);
        if ($stmt->execute()) {
            echo "Created category: " . $category['name'] . "\n";
        } else {
            echo "Error creating category " . $category['name'] . ": " . $stmt->error . "\n";
        }
    } else {
        echo "Category already exists: " . $category['name'] . "\n";
    }
}

$stmt->close();
$db->close();

echo "\nCategories setup completed.";
