<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Get date from URL, default to today
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$page_title = 'Daily Sales Report: ' . date('F d, Y', strtotime($date));
include_once '../../includes/header.php';

// Get daily sales summary
$query = "SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_sales,
            SUM(received_amount) as total_received,
            SUM(change_amount) as total_change,
            MIN(total_amount) as min_sale,
            MAX(total_amount) as max_sale,
            AVG(total_amount) as avg_sale
          FROM orders 
          WHERE DATE(order_date) = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Get sales by payment method
$query = "SELECT 
            payment_method,
            COUNT(*) as order_count,
            SUM(total_amount) as method_total
          FROM orders 
          WHERE DATE(order_date) = ?
          GROUP BY payment_method";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);
$payment_methods = mysqli_stmt_get_result($stmt);

// Get hourly sales
$query = "SELECT 
            HOUR(order_date) as hour,
            COUNT(*) as order_count,
            SUM(total_amount) as hour_total
          FROM orders 
          WHERE DATE(order_date) = ?
          GROUP BY HOUR(order_date)
          ORDER BY hour";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);
$hourly_sales = mysqli_stmt_get_result($stmt);

// Get detailed orders
$query = "SELECT 
            o.*,
            u.username as cashier_name
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.user_id
          WHERE DATE(o.order_date) = ?
          ORDER BY o.order_date DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
?>

<div class="container-fluid mt-4">
    <!-- Date Navigation -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="form-inline justify-content-between">
                <div class="btn-group">
                    <a href="?date=<?php echo date('Y-m-d', strtotime($date . ' -1 day')); ?>" 
                       class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i> Previous Day
                    </a>
                    <input type="date" name="date" class="form-control mx-2" 
                           value="<?php echo $date; ?>" onchange="this.form.submit()">
                    <a href="?date=<?php echo date('Y-m-d', strtotime($date . ' +1 day')); ?>" 
                       class="btn btn-secondary">
                        Next Day <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <a href="export.php?date=<?php echo $date; ?>" class="btn btn-success">
                    <i class="fas fa-download"></i> Export Daily Report
                </a>
            </form>
        </div>
    </div>

    <!-- Sales Summary -->
    <div class="row">
        <div class="col-md-3">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Total Orders</h5>
                    <h3 class="mb-0"><?php echo $summary['total_orders']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Total Sales</h5>
                    <h3 class="mb-0"><?php echo formatCurrency($summary['total_sales']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Average Sale</h5>
                    <h3 class="mb-0"><?php echo formatCurrency($summary['avg_sale']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <h5 class="card-title">Peak Hour</h5>
                    <h3 class="mb-0">
                        <?php
                        $max_hour = 0;
                        $max_sales = 0;
                        mysqli_data_seek($hourly_sales, 0);
                        while ($hour = mysqli_fetch_assoc($hourly_sales)) {
                            if ($hour['hour_total'] > $max_sales) {
                                $max_sales = $hour['hour_total'];
                                $max_hour = $hour['hour'];
                            }
                        }
                        echo date('h:00 A', strtotime($max_hour . ':00'));
                        ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Hourly Sales</h5>
                </div>
                <div class="card-body">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payment Methods</h5>
                </div>
                <div class="card-body">
                    <canvas id="paymentChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Orders -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Order Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Time</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Cashier</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                            <tr>
                                <td><?php echo $order['receipt_number']; ?></td>
                                <td><?php echo date('h:i A', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <?php
                                    $items_query = "SELECT COUNT(*) as item_count FROM order_items WHERE order_id = ?";
                                    $stmt = mysqli_prepare($conn, $items_query);
                                    mysqli_stmt_bind_param($stmt, "i", $order['order_id']);
                                    mysqli_stmt_execute($stmt);
                                    $items = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                                    echo $items['item_count'];
                                    ?>
                                </td>
                                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $order['payment_method'] == 'cash' ? 'success' : 'info'; ?>">
                                        <?php echo ucfirst($order['payment_method']); ?>
                                    </span>
                                </td>
                                <td><?php echo $order['cashier_name']; ?></td>
                                <td>
                                    <a href="../pos/receipt.php?order_id=<?php echo $order['order_id']; ?>" 
                                       class="btn btn-sm btn-info" target="_blank">
                                        <i class="fas fa-receipt"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare data for hourly chart
<?php
mysqli_data_seek($hourly_sales, 0);
$hours = [];
$sales = [];
while ($row = mysqli_fetch_assoc($hourly_sales)) {
    $hours[] = date('h:00 A', strtotime($row['hour'] . ':00'));
    $sales[] = $row['hour_total'];
}
?>

// Hourly Sales Chart
new Chart(document.getElementById('hourlyChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($hours); ?>,
        datasets: [{
            label: 'Sales',
            data: <?php echo json_encode($sales); ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgb(75, 192, 192)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Payment Methods Chart
<?php
mysqli_data_seek($payment_methods, 0);
$methods = [];
$totals = [];
while ($row = mysqli_fetch_assoc($payment_methods)) {
    $methods[] = ucfirst($row['payment_method']);
    $totals[] = $row['method_total'];
}
?>

new Chart(document.getElementById('paymentChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode($methods); ?>,
        datasets: [{
            data: <?php echo json_encode($totals); ?>,
            backgroundColor: [
                'rgb(255, 99, 132)',
                'rgb(54, 162, 235)',
                'rgb(255, 205, 86)'
            ]
        }]
    }
});

// Initialize DataTable
$(document).ready(function() {
    $('#ordersTable').DataTable({
        "order": [[1, "desc"]],
        "pageLength": 25
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?>
