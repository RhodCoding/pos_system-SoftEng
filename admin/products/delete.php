<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['product_id'];

    // Check if product exists before deletion
    $check_query = "SELECT COUNT(*) as count FROM products WHERE product_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $product_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $exists = mysqli_fetch_assoc($check_result)['count'];

    if ($exists == 0) {
        $_SESSION['error'] = "Product not found";
        redirect('admin/products/index.php');
    }

    $query = "DELETE FROM products WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Product deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting product";
    }
    redirect('admin/products/index.php');
} else {
    $_SESSION['error'] = "Invalid request";
    redirect('admin/products/index.php');
}
?>
