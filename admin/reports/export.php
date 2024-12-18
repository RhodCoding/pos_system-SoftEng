<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Get export type and date parameters
$type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Set filename based on report type
$filename = $type == 'daily' 
    ? "daily_sales_report_{$date}.csv"
    : "sales_report_{$start_date}_to_{$end_date}.csv";

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create file pointer connected to PHP output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($type == 'daily') {
    // Export daily sales report
    // Add CSV headers
    fputcsv($output, [
        'Receipt Number',
        'Time',
        'Items',
        'Total Amount',
        'Payment Method',
        'Received Amount',
        'Change',
        'Cashier'
    ]);

    // Get orders for the day
    $query = "SELECT o.*, u.username as cashier_name,
              (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.user_id
              WHERE DATE(o.order_date) = ?
              ORDER BY o.order_date";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Output each row
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [
            $row['receipt_number'],
            date('h:i A', strtotime($row['order_date'])),
            $row['item_count'],
            number_format($row['total_amount'], 2),
            ucfirst($row['payment_method']),
            number_format($row['received_amount'], 2),
            number_format($row['change_amount'], 2),
            $row['cashier_name']
        ]);
    }

    // Add summary row
    $summary_query = "SELECT 
                       COUNT(*) as total_orders,
                       SUM(total_amount) as total_sales,
                       AVG(total_amount) as avg_sale
                     FROM orders 
                     WHERE DATE(order_date) = ?";
    
    $stmt = mysqli_prepare($conn, $summary_query);
    mysqli_stmt_bind_param($stmt, "s", $date);
    mysqli_stmt_execute($stmt);
    $summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // Add empty row
    fputcsv($output, []);
    
    // Add summary
    fputcsv($output, ['Summary']);
    fputcsv($output, ['Total Orders', $summary['total_orders']]);
    fputcsv($output, ['Total Sales', number_format($summary['total_sales'], 2)]);
    fputcsv($output, ['Average Sale', number_format($summary['avg_sale'], 2)]);

} else {
    // Export date range report
    // Add CSV headers
    fputcsv($output, [
        'Date',
        'Orders',
        'Total Sales',
        'Cash Sales',
        'Card Sales',
        'Average Sale',
        'Top Product',
        'Top Product Sales'
    ]);

    // Get daily summaries
    $query = "SELECT 
                DATE(order_date) as sale_date,
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                SUM(CASE WHEN payment_method = 'cash' THEN total_amount ELSE 0 END) as cash_sales,
                SUM(CASE WHEN payment_method = 'card' THEN total_amount ELSE 0 END) as card_sales,
                AVG(total_amount) as avg_sale
              FROM orders 
              WHERE DATE(order_date) BETWEEN ? AND ?
              GROUP BY DATE(order_date)
              ORDER BY sale_date";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        // Get top product for each day
        $top_product_query = "SELECT 
                               p.name,
                               SUM(oi.quantity) as total_quantity
                             FROM order_items oi
                             JOIN products p ON oi.product_id = p.product_id
                             JOIN orders o ON oi.order_id = o.order_id
                             WHERE DATE(o.order_date) = ?
                             GROUP BY p.product_id
                             ORDER BY total_quantity DESC
                             LIMIT 1";
        
        $stmt2 = mysqli_prepare($conn, $top_product_query);
        mysqli_stmt_bind_param($stmt2, "s", $row['sale_date']);
        mysqli_stmt_execute($stmt2);
        $top_product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

        fputcsv($output, [
            date('Y-m-d', strtotime($row['sale_date'])),
            $row['total_orders'],
            number_format($row['total_sales'], 2),
            number_format($row['cash_sales'], 2),
            number_format($row['card_sales'], 2),
            number_format($row['avg_sale'], 2),
            $top_product['name'] ?? 'N/A',
            $top_product['total_quantity'] ?? 0
        ]);
    }

    // Add summary
    $summary_query = "SELECT 
                       COUNT(*) as total_orders,
                       SUM(total_amount) as total_sales,
                       AVG(total_amount) as avg_sale
                     FROM orders 
                     WHERE DATE(order_date) BETWEEN ? AND ?";
    
    $stmt = mysqli_prepare($conn, $summary_query);
    mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
    mysqli_stmt_execute($stmt);
    $summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // Add empty row
    fputcsv($output, []);
    
    // Add summary
    fputcsv($output, ['Period Summary']);
    fputcsv($output, ['Date Range', "$start_date to $end_date"]);
    fputcsv($output, ['Total Orders', $summary['total_orders']]);
    fputcsv($output, ['Total Sales', number_format($summary['total_sales'], 2)]);
    fputcsv($output, ['Average Sale', number_format($summary['avg_sale'], 2)]);
}

// Close the file pointer
fclose($output);
exit();
?>
