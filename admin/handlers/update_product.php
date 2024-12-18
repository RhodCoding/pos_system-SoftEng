<?php
require_once '../../includes/session.php';
require_once '../../classes/Product.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $product = new Product();
    $productId = $_POST['id'];
    
    // Handle image upload
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/products/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            // Delete old image if exists
            $oldProduct = $product->findById($productId);
            if ($oldProduct && $oldProduct['image']) {
                $oldImagePath = $uploadDir . $oldProduct['image'];
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            
            $image = uniqid() . '.' . $fileExtension;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image);
        }
    }
    
    // Prepare product data
    $productData = [
        'name' => $_POST['name'],
        'category_id' => $_POST['category_id'],
        'price' => $_POST['price'],
        'stock' => $_POST['stock'],
        'status' => $_POST['status']
    ];
    
    // Only add image to update data if a new one was uploaded
    if ($image) {
        $productData['image'] = $image;
    }
    
    // Update the product
    $result = $product->update($productId, $productData);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update product']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
