<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include_once '../includes/employee_header.php'; // Include the employee header

// Initialize response array
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $product_id = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];

            // Validate quantity
            if ($quantity <= 0) {
                $response['message'] = 'Invalid quantity';
                break;
            }

            // Check product stock
            $query = "SELECT * FROM products WHERE product_id = ? AND stock >= ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $product_id, $quantity);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($product = mysqli_fetch_assoc($result)) {
                // Add to cart
                if (addToCart($product_id, $quantity)) {
                    $response['success'] = true;
                    $response['message'] = 'Item added to cart';
                    $response['cart'] = $_SESSION['cart'];
                } else {
                    $response['message'] = 'Error adding item to cart';
                }
            } else {
                $response['message'] = 'Insufficient stock';
            }
            break;

        case 'remove':
            $product_id = (int)$_POST['product_id'];
            
            if (isset($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $key => $item) {
                    if ($item['product_id'] == $product_id) {
                        unset($_SESSION['cart'][$key]);
                        $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
                        $response['success'] = true;
                        $response['message'] = 'Item removed from cart';
                        break;
                    }
                }
            }
            break;

        case 'update':
            $product_id = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];

            if ($quantity <= 0) {
                $response['message'] = 'Invalid quantity';
                break;
            }

            // Check stock
            $query = "SELECT * FROM products WHERE product_id = ? AND stock >= ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $product_id, $quantity);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($product = mysqli_fetch_assoc($result)) {
                if (isset($_SESSION['cart'])) {
                    foreach ($_SESSION['cart'] as &$item) {
                        if ($item['product_id'] == $product_id) {
                            $item['quantity'] = $quantity;
                            $response['success'] = true;
                            $response['message'] = 'Quantity updated';
                            break;
                        }
                    }
                }
            } else {
                $response['message'] = 'Insufficient stock';
            }
            break;

        case 'clear':
            $_SESSION['cart'] = array();
            $response['success'] = true;
            $response['message'] = 'Cart cleared';
            break;

        default:
            $response['message'] = 'Invalid action';
            break;
    }
}

// Calculate cart totals
if ($response['success']) {
    $total = 0;
    $items = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
            $items += $item['quantity'];
        }
    }
    $response['total'] = $total;
    $response['items'] = $items;
}

echo json_encode($response);
?>
