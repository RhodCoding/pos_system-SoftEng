<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    die('Unauthorized access');
}

include_once 'C:/xampp/htdocs/pos_system/pos/includes/header_employee.php';

// Get order ID
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Get order details
$query = "SELECT o.*, u.full_name as cashier_name 
          FROM orders o 
          JOIN users u ON o.user_id = u.user_id 
          WHERE o.order_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$order) {
    die('Order not found');
}

// Get order items
$query = "SELECT oi.*, p.name as product_name 
          FROM order_items oi 
          JOIN products p ON oi.product_id = p.product_id 
          WHERE oi.order_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?php echo $order_id; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .receipt {
            width: 300px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .store-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .store-info {
            margin-bottom: 5px;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .totals {
            margin-top: 10px;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="store-name"><?php echo getSetting('store_name', 'My Store'); ?></div>
            <div class="store-info"><?php echo getSetting('store_address', ''); ?></div>
            <div class="store-info"><?php echo getSetting('store_phone', ''); ?></div>
            <div class="store-info"><?php echo getSetting('receipt_header', ''); ?></div>
        </div>

        <div class="divider"></div>

        <!-- Order Info -->
        <div class="order-info">
            <div>Receipt #: <?php echo str_pad($order_id, 8, '0', STR_PAD_LEFT); ?></div>
            <div>Date: <?php echo date('Y-m-d h:i A', strtotime($order['order_date'])); ?></div>
            <div>Cashier: <?php echo $order['cashier_name']; ?></div>
        </div>

        <div class="divider"></div>

        <!-- Items -->
        <?php while ($item = mysqli_fetch_assoc($items)): ?>
            <div class="item">
                <div>
                    <?php echo $item['product_name']; ?><br>
                    <?php echo $item['quantity']; ?> @ <?php echo formatCurrency($item['price']); ?>
                </div>
                <div><?php echo formatCurrency($item['subtotal']); ?></div>
            </div>
        <?php endwhile; ?>

        <div class="divider"></div>

        <!-- Totals -->
        <div class="totals">
            <div class="total-line">
                <div>Subtotal:</div>
                <div><?php echo formatCurrency($order['total_amount'] - $order['tax_amount']); ?></div>
            </div>
            <div class="total-line">
                <div>Tax:</div>
                <div><?php echo formatCurrency($order['tax_amount']); ?></div>
            </div>
            <div class="total-line">
                <div><strong>Total:</strong></div>
                <div><strong><?php echo formatCurrency($order['total_amount']); ?></strong></div>
            </div>
            <div class="total-line">
                <div>Amount Received:</div>
                <div><?php echo formatCurrency($order['received_amount']); ?></div>
            </div>
            <div class="total-line">
                <div>Change:</div>
                <div><?php echo formatCurrency($order['received_amount'] - $order['total_amount']); ?></div>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Footer -->
        <div class="footer">
            <div><?php echo getSetting('receipt_footer', 'Thank you for your purchase!'); ?></div>
            <div>Payment Method: <?php echo ucfirst($order['payment_method']); ?></div>
        </div>

        <!-- Print Button -->
        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print()">Print Receipt</button>
        </div>
    </div>

    <script>
        // Auto print on page load
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
