<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if the user is logged in
if (!isLoggedIn()) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Get the product IDs from the POST request
$product_ids = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];

// Prepare the response array
$response = [];

// Fetch updated stock levels for each product
foreach ($product_ids as $product_id) {
    $query = "SELECT stock FROM products WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    
    if ($product) {
        $response[$product_id] = $product['stock'];
    }
}

// Return the updated stock levels as JSON
echo json_encode(['success' => true, 'stocks' => $response]);
?>
