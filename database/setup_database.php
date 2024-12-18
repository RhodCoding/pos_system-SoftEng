<?php
require_once __DIR__ . '/../config/database.php';

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    category_id INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($db->query($sql)) {
    echo "Products table created successfully\n";
} else {
    echo "Error creating products table: " . $db->error . "\n";
}

// Insert sample products
$sampleProducts = [
    [
        'name' => 'Pandesal',
        'description' => 'Fresh and soft Filipino bread rolls',
        'price' => 5.00,
        'stock_quantity' => 100
    ],
    [
        'name' => 'Ensaymada',
        'description' => 'Soft and fluffy Filipino bread topped with butter and cheese',
        'price' => 20.00,
        'stock_quantity' => 50
    ],
    [
        'name' => 'Chocolate Cake',
        'description' => 'Rich and moist chocolate cake',
        'price' => 450.00,
        'stock_quantity' => 5
    ]
];

// Clear existing products
$db->query("DELETE FROM products");

// Insert new products
foreach ($sampleProducts as $product) {
    $sql = "INSERT INTO products (name, description, price, stock_quantity) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssdi", 
        $product['name'],
        $product['description'],
        $product['price'],
        $product['stock_quantity']
    );
    
    if ($stmt->execute()) {
        echo "Created product: " . $product['name'] . "\n";
    } else {
        echo "Error creating " . $product['name'] . ": " . $stmt->error . "\n";
    }
}

$db->close();

echo "\nDatabase setup completed. You can now refresh the POS page.";
