<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    die(json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]));
}

// Set header to return JSON
header('Content-Type: application/json');

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]));
}

// Get POST data
$cart = isset($_POST['cart']) ? json_decode($_POST['cart'], true) : [];
$payment_method = clean($_POST['payment_method']);
$received_amount = floatval($_POST['received_amount']);
$total_amount = floatval($_POST['total_amount']);
$tax_amount = floatval($_POST['tax_amount']);

// Validate data
if (empty($cart)) {
    die(json_encode([
        'success' => false,
        'message' => 'Cart is empty'
    ]));
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Insert order
    $order_query = "INSERT INTO orders (user_id, total_amount, tax_amount, 
                                      payment_method, received_amount) 
                   VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $order_query);
    mysqli_stmt_bind_param($stmt, "iddsd", 
        $_SESSION['user_id'],
        $total_amount,
        $tax_amount,
        $payment_method,
        $received_amount
    );
    mysqli_stmt_execute($stmt);
    $order_id = mysqli_insert_id($conn);

    // Insert order items and update stock
    foreach ($cart as $item) {
        // Verify current stock
        $stock_query = "SELECT stock FROM products WHERE product_id = ?";
        $stmt = mysqli_prepare($conn, $stock_query);
        mysqli_stmt_bind_param($stmt, "i", $item['id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($result);

        if (!$product) {
            throw new Exception("Product not found: " . $item['name']);
        }

        if ($product['stock'] < $item['quantity']) {
            throw new Exception("Insufficient stock for: " . $item['name']);
        }

        // Insert order item
        $item_query = "INSERT INTO order_items (order_id, product_id, quantity, 
                                              price, subtotal) 
                      VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $item_query);
        $subtotal = $item['price'] * $item['quantity'];
        mysqli_stmt_bind_param($stmt, "iiidd", 
            $order_id,
            $item['id'],
            $item['quantity'],
            $item['price'],
            $subtotal
        );
        mysqli_stmt_execute($stmt);

        // Update stock
        $update_query = "UPDATE products 
                        SET stock = stock - ? 
                        WHERE product_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ii", 
            $item['quantity'],
            $item['id']
        );
        mysqli_stmt_execute($stmt);
    }

    // Commit transaction
    mysqli_commit($conn);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order processed successfully',
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
