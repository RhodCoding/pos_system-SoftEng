<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Initialize response array
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate cart
    if (empty($_SESSION['cart'])) {
        $response['message'] = 'Cart is empty';
        echo json_encode($response);
        exit;
    }

    // Get form data
    $payment_method = clean($_POST['payment_method']);
    $received_amount = (float)$_POST['received_amount'];

    // Calculate total
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    // Validate received amount
    if ($received_amount < $total_amount) {
        $response['message'] = 'Insufficient payment amount';
        echo json_encode($response);
        exit;
    }

    try {
        mysqli_begin_transaction($conn);

        // Create order
        $receipt_number = generateReceiptNumber();
        $query = "INSERT INTO orders (receipt_number, total_amount, payment_method, 
                                    received_amount, change_amount, user_id) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        $change_amount = $received_amount - $total_amount;
        mysqli_stmt_bind_param($stmt, "sdsddi", $receipt_number, $total_amount, 
                             $payment_method, $received_amount, $change_amount, 
                             $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $order_id = mysqli_insert_id($conn);

        // Insert order items and update stock
        foreach ($_SESSION['cart'] as $item) {
            // Insert order item
            $query = "INSERT INTO order_items (order_id, product_id, quantity, 
                                             unit_price, subtotal) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            $subtotal = $item['price'] * $item['quantity'];
            mysqli_stmt_bind_param($stmt, "iiidd", $order_id, $item['product_id'], 
                                 $item['quantity'], $item['price'], $subtotal);
            mysqli_stmt_execute($stmt);

            // Update stock
            $query = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $item['quantity'], $item['product_id']);
            mysqli_stmt_execute($stmt);
        }

        mysqli_commit($conn);

        // Clear cart
        $_SESSION['cart'] = array();

        // Prepare response
        $response['success'] = true;
        $response['message'] = 'Order completed successfully';
        $response['order'] = [
            'order_id' => $order_id,
            'receipt_number' => $receipt_number,
            'total' => $total_amount,
            'received' => $received_amount,
            'change' => $change_amount
        ];
        $response['receipt_url'] = 'receipt.php?order_id=' . $order_id;

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $response['message'] = 'Error processing order: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>
