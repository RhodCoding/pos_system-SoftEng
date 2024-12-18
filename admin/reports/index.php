<?php
error_reporting(E_ALL); // Report all types of errors
ini_set('display_errors', 1);

session_start(); 
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

$page_title = 'Sales Reports';
include_once '../../includes/header.php';

// Get date range from URL parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get sales summary
$query = "SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_sales,
            AVG(total_amount) as average_sale,
            payment_method,
            DATE(order_date) as sale_date
          FROM orders 
          WHERE DATE(order_date) BETWEEN ? AND ?
          GROUP BY DATE(order_date), payment_method
          ORDER BY sale_date DESC";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$sales_result = mysqli_stmt_get_result($stmt);

// Get top selling products
$query = "SELECT 
            p.product_id,
            p.name,
            c.name as category_name,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.subtotal) as total_sales
          FROM order_items oi
          JOIN products p ON oi.product_id = p.product_id
          JOIN orders o ON oi.order_id = o.order_id
          LEFT JOIN categories c ON p.category_id = c.category_id
          WHERE DATE(o.order_date) BETWEEN ? AND ?
          GROUP BY p.product_id
          ORDER BY total_quantity DESC
          LIMIT 10";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$top_products_result = mysqli_stmt_get_result($stmt);
?>

<div class="container-fluid mt-4">
    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label class="mr-2">Start Date:</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?php echo $start_date; ?>">
                </div>
                <div class="form-group mr-3">
                    <label class="mr-2">End Date:</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?php echo $end_date; ?>">
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="export.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" 
                   class="btn btn-success ml-2">
                    <i class="fas fa-download"></i> Export Report
                </a>
            </form>
        </div>
    </div>

    <!-- Sales Summary -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Sales Summary</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart"></canvas>
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

    <!-- Top Selling Products -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Top Selling Products</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Quantity Sold</th>
                            <th>Total Sales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = mysqli_fetch_assoc($top_products_result)): ?>
                            <tr>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td><?php echo $product['total_quantity']; ?></td>
                                <td><?php echo formatCurrency($product['total_sales']); ?></td>
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
// Prepare data for charts
<?php
// Reset result pointer
mysqli_data_seek($sales_result, 0);

$dates = [];
$sales = [];
$payment_methods = [];
$payment_totals = [];

while ($row = mysqli_fetch_assoc($sales_result)) {
    if (!in_array($row['sale_date'], $dates)) {
        $dates[] = $row['sale_date'];
    }
    if (!isset($payment_methods[$row['payment_method']])) {
        $payment_methods[$row['payment_method']] = 0;
    }
    $payment_methods[$row['payment_method']] += $row['total_sales'];
}
?>

// Sales Chart
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dates); ?>,
        datasets: [{
            label: 'Daily Sales',
            data: <?php echo json_encode(array_values($sales)); ?>,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Payment Methods Chart
new Chart(document.getElementById('paymentChart'), {
    type: 'pie',
    data: {
        labels: <?php echo json_encode(array_keys($payment_methods)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_values($payment_methods)); ?>,
            backgroundColor: [
                'rgb(255, 99, 132)',
                'rgb(54, 162, 235)',
                'rgb(255, 205, 86)'
            ]
        }]
    },
    options: {
        responsive: true
    }
});
</script>

<?php include_once '../../includes/footer.php'; ?>
