<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="stock_report_' . date('Y-m-d') . '.csv"');

// Create file pointer connected to PHP output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, [
    'Product ID',
    'Product Name',
    'Category',
    'Current Stock',
    'Price',
    'Last Updated',
    'Status'
]);

// Get all products with categories
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          ORDER BY c.name, p.name";
$result = mysqli_query($conn, $query);

// Output each row
while ($row = mysqli_fetch_assoc($result)) {
    // Determine stock status
    $status = 'Normal';
    if ($row['stock'] <= 0) {
        $status = 'Out of Stock';
    } elseif ($row['stock'] < 10) {
        $status = 'Low Stock';
    }

    fputcsv($output, [
        $row['product_id'],
        $row['name'],
        $row['category_name'],
        $row['stock'],
        number_format($row['price'], 2),
        date('Y-m-d H:i', strtotime($row['updated_at'])),
        $status
    ]);
}

// Close the file pointer
fclose($output);
exit();
?>
