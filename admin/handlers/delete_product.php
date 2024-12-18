<?php
require_once '../../includes/session.php';
require_once '../../classes/Product.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product = new Product();
    $productId = $_POST['product_id'];
    
    // Get product info to delete image if exists
    $productInfo = $product->findById($productId);
    
    if ($productInfo && $productInfo['image']) {
        $imagePath = "../../uploads/products/" . $productInfo['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath); // Delete the image file
        }
    }
    
    // Delete the product
    $result = $product->delete($productId);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
