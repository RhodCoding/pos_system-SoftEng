<?php
require_once '../../includes/session.php';
require_once '../../classes/Product.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product = new Product();
    
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
        'status' => $_POST['status'],
        'image' => $image
    ];
    
    // Add the product
    $result = $product->create($productData);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product']);
    }
    exit;
}
