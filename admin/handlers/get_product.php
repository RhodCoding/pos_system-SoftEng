<?php
require_once '../../includes/session.php';
require_once '../../classes/Product.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $product = new Product();
    $productId = $_GET['id'];
    
    $productInfo = $product->findById($productId);
    
    if ($productInfo) {
        echo json_encode(['success' => true, 'data' => $productInfo]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
