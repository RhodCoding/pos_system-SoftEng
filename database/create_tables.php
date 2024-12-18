<?php
require_once __DIR__ . '/../config/database.php';

$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Drop existing tables in correct order
$db->query("SET FOREIGN_KEY_CHECKS = 0");
$db->query("DROP TABLE IF EXISTS order_items");
$db->query("DROP TABLE IF EXISTS orders");
$db->query("DROP TABLE IF EXISTS products");
$db->query("DROP TABLE IF EXISTS users");
$db->query("DROP TABLE IF EXISTS categories");
$db->query("SET FOREIGN_KEY_CHECKS = 1");

// Create categories table
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($db->query($sql) === TRUE) {
    echo "Categories table created successfully\n";
} else {
    echo "Error creating categories table: " . $db->error . "\n";
}

// Create users table
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

if ($db->query($sql) === TRUE) {
    echo "Users table created successfully\n";
} else {
    echo "Error creating users table: " . $db->error . "\n";
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
)";

if ($db->query($sql) === TRUE) {
    echo "Products table created successfully\n";
} else {
    echo "Error creating products table: " . $db->error . "\n";
}

// Create orders table
$sql = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($db->query($sql) === TRUE) {
    echo "Orders table created successfully\n";
} else {
    echo "Error creating orders table: " . $db->error . "\n";
}

// Create order_items table
$sql = "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)";

if ($db->query($sql) === TRUE) {
    echo "Order items table created successfully\n";
} else {
    echo "Error creating order items table: " . $db->error . "\n";
}

// Insert default category
$sql = "INSERT INTO categories (name, description) VALUES ('Bread', 'Fresh bread and pastries')";

if ($db->query($sql) === TRUE) {
    echo "Default category created successfully\n";
} else {
    echo "Error creating default category: " . $db->error . "\n";
}

// Create default admin and employee accounts
$adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$employeePassword = password_hash('employee123', PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, password, name, role) VALUES 
        ('admin', ?, 'System Administrator', 'admin'),
        ('employee1', ?, 'Sample Employee', 'employee')";

$stmt = $db->prepare($sql);
$stmt->bind_param("ss", $adminPassword, $employeePassword);

if ($stmt->execute()) {
    echo "Default users created successfully\n";
} else {
    echo "Error creating default users: " . $stmt->error . "\n";
}

$db->close();
