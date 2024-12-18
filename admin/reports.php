<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../classes/Reports.php'; // Include the Reports class

$reports = new Reports(); // Instantiate the Reports class
$todaySales = $reports->getTodaySales(); // Get today's sales
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Bakery POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block sidebar">
                <?php include 'components/sidebar.php'; ?>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3">
                    <h2>Reports</h2>
                    <div>
                        <button class="btn btn-success" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Reports Content -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Sales Reports</h5>
                            </div>
                            <div class="card-body">
                                <!-- Today's Summary -->
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="card text-white bg-primary h-100">
                                            <div class="card-body">
                                                <h5 class="card-title">Today's Total Sales</h5>
                                                <h2 class="mb-0">₱<?php echo number_format($todaySales, 2); ?></h2>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card text-white bg-success h-100">
                                            <div class="card-body">
                                                <h5 class="card-title">Total Pieces Sold</h5>
                                                <h2 class="mb-0">0 pcs</h2>
                                                <small class="text-white-50">Bread & Pastries</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card text-white bg-info h-100">
                                            <div class="card-body">
                                                <h5 class="card-title">Best Seller Today</h5>
                                                <h2 class="mb-0">Pandesal</h2>
                                                <small class="text-white-50">0 pcs sold</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Charts -->
                                <div class="row mb-4">
                                    <div class="col-md-8">
                                        <div class="card">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h5 class="card-title mb-0">Sales by Hour</h5>
                                                <small class="text-muted">Most sales happen during breakfast time</small>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="hourlySalesChart" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h5 class="card-title mb-0">Top Selling Items</h5>
                                                <small class="text-muted">By quantity sold</small>
                                            </div>
                                            <div class="card-body">
                                                <canvas id="topItemsChart" height="300"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sales List -->
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">Today's Sales</h5>
                                        <small class="text-muted">All transactions are cash payments</small>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Time</th>
                                                        <th>Items</th>
                                                        <th>Total</th>
                                                        <th>Payment</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Sales will be populated here -->
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <td colspan="2"><strong>Daily Total</strong></td>
                                                        <td><strong>₱<?php echo number_format($todaySales, 2); ?></strong></td>
                                                        <td>Cash</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                                    <h1>Daily Sales Report</h1>
                                    <div class="btn-toolbar mb-2 mb-md-0">
                                        <button type="button" class="btn btn-outline-secondary me-2" onclick="window.print()">
                                            <i class="bi bi-printer"></i> Print Report
                                        </button>
                                        <input type="date" class="form-control" id="reportDate" value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/reports.js"></script>
</body>
</html>
