<?php
require_once __DIR__ . '/../config/database.php';

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Delete existing products safely
$db->query("DELETE FROM order_items");
$db->query("DELETE FROM products");

$sampleProducts = [
    [
        'name' => 'Pandesal',
        'description' => 'Fresh and soft Filipino bread rolls',
        'price' => 5.00,
        'stock_quantity' => 100,
        'category_id' => 1
    ],
    [
        'name' => 'Ensaymada',
        'description' => 'Soft and fluffy Filipino bread topped with butter and cheese',
        'price' => 20.00,
        'stock_quantity' => 50,
        'category_id' => 1
    ],
    [
        'name' => 'Chocolate Cake',
        'description' => 'Rich and moist chocolate cake',
        'price' => 450.00,
        'stock_quantity' => 5,
        'category_id' => 1
    ]
];

foreach ($sampleProducts as $product) {
    $sql = "INSERT INTO products (name, description, price, stock_quantity, category_id) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssdii", 
        $product['name'],
        $product['description'],
        $product['price'],
        $product['stock_quantity'],
        $product['category_id']
    );
    
    if ($stmt->execute()) {
        echo "Created product: " . $product['name'] . "\n";
    } else {
        echo "Error creating " . $product['name'] . ": " . $stmt->error . "\n";
    }
    $stmt->close();
}

$db->close();
