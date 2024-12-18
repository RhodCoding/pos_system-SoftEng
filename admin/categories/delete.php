<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if category has products
$check_query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$count = mysqli_fetch_assoc($result)['count'];

if ($count > 0) {
    $_SESSION['error'] = "Cannot delete category: It contains products";
    redirect('admin/categories/index.php');
}

// Delete the category
$query = "DELETE FROM categories WHERE category_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $category_id);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = "Category deleted successfully";
} else {
    $_SESSION['error'] = "Error deleting category";
}

// Redirect back to the categories index
redirect('admin/categories/index.php');
?>
