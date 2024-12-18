<?php
// includes/functions.php

// Get total sales for today
function getTodaySales() {
    global $conn;
    $query = "SELECT SUM(total_amount) as total FROM orders 
              WHERE DATE(order_date) = CURDATE()";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'] ?? 0;
}

// Get low stock products
function getLowStockProducts() {
    global $conn;
    $query = "SELECT * FROM products WHERE stock < 10";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Format currency
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

// Generate receipt number
function generateReceiptNumber() {
    return 'RCP-' . date('Ymd') . '-' . rand(1000, 9999);
}

// Add to cart
function addToCart($product_id, $quantity = 1) {
    global $conn;
    
    $query = "SELECT * FROM products WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);

    if ($product) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = array();
        }

        // Check if product already in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $_SESSION['cart'][] = array(
                'product_id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity
            );
        }
        return true;
    }
    return false;
}

// Process order
function processOrder($payment_method) {
    global $conn;
    
    if (empty($_SESSION['cart'])) {
        return false;
    }

    try {
        mysqli_begin_transaction($conn);

        $total_amount = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }

        // Insert order
        $receipt_number = generateReceiptNumber();
        $query = "INSERT INTO orders (receipt_number, total_amount, payment_method, user_id) 
                 VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sdsi", $receipt_number, $total_amount, 
                             $payment_method, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $order_id = mysqli_insert_id($conn);

        // Insert order items and update stock
        foreach ($_SESSION['cart'] as $item) {
            $query = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
                     VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iiid", $order_id, $item['product_id'], 
                                 $item['quantity'], $item['price']);
            mysqli_stmt_execute($stmt);

            // Update stock
            $query = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $item['quantity'], $item['product_id']);
            mysqli_stmt_execute($stmt);
        }

        mysqli_commit($conn);
        $_SESSION['cart'] = array();
        return $order_id;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        return false;
    }
}

/**
 * Get a setting value from the settings table
 * @param string $key The setting key to retrieve
 * @param mixed $default Default value if setting not found
 * @return mixed The setting value or default value
 */
function getSetting($key, $default = null) {
    global $conn;
    
    $query = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'];
    }
    
    return $default;
}
?>
