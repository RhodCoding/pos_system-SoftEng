<?php
session_start();
require_once '../classes/Dashboard.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

$dashboard = new Dashboard();
$todaySales = $dashboard->getTodaySales();
$totalProducts = $dashboard->getTotalProducts();
$totalCategories = $dashboard->getTotalCategories();
$lowStockItems = $dashboard->getLowStockItems();
$employees = $dashboard->getEmployees();
$lastSevenDaysSales = $dashboard->getLastSevenDaysSales();
$topProducts = $dashboard->getTopSellingProducts();
$hourlyStats = $dashboard->getSalesByHour();
$recentOrders = $dashboard->getRecentOrders();
$categoryStats = $dashboard->getCategoryDistribution();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bakery POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <h2>Dashboard</h2>
                    <div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                            <i class="fas fa-user-plus"></i> Add Employee
                        </button>
                        <button class="btn btn-success" id="refreshBtn" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Today's Sales</h5>
                                <h2 class="mb-0">₱<?php echo number_format($todaySales['total'], 2); ?></h2>
                                <small>Orders: <?php echo $todaySales['order_count']; ?> | Items: <?php echo $todaySales['items_sold']; ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Products</h5>
                                <h2 class="mb-0"><?php echo $totalProducts; ?></h2>
                                <small><?php echo $lowStockItems; ?> items low on stock</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Categories</h5>
                                <h2 class="mb-0"><?php echo $totalCategories; ?></h2>
                                <small>Active product categories</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Low Stock Items</h5>
                                <h2 class="mb-0"><?php echo $lowStockItems; ?></h2>
                                <small>Items need restocking</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Sales Chart -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Sales Last 7 Days</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="salesChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hourly Sales Chart -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Today's Hourly Sales</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="hourlyChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Top Products -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Top Selling Products</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Quantity Sold</th>
                                                <th>Total Sales</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topProducts as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo $product['total_quantity']; ?></td>
                                                <td>₱<?php echo number_format($product['total_sales'], 2); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Orders</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Cashier</th>
                                                <th>Amount</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentOrders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo htmlspecialchars($order['cashier_name']); ?></td>
                                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employee Performance -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Employee Performance</h5>
                    </div>
                    <div class="card-body">
                        <table class="table" id="employeeTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                require_once '../classes/Employee.php';
                                $employee = new Employee();
                                $employees = $employee->getAllEmployees();
                                
                                foreach ($employees as $emp) {
                                    echo "<tr>";
                                    echo "<td>{$emp['name']}</td>";
                                    echo "<td>{$emp['username']}</td>";
                                    echo "<td>{$emp['status']}</td>";
                                    echo "<td>";
                                    echo "<button class='btn btn-sm btn-primary' onclick='editEmployee({$emp['id']})'><i class='fas fa-edit'></i></button> ";
                                    echo "<button class='btn btn-sm btn-danger' onclick='deleteEmployee({$emp['id']})'><i class='fas fa-trash'></i></button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addEmployeeForm" onsubmit="return false;">
                        <div class="mb-3">
                            <label for="employeeName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="employeeName" name="name" required>
                            <div class="invalid-feedback" id="nameError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="employeeUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="employeeUsername" name="username" required>
                            <div class="invalid-feedback" id="usernameError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="employeePassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="employeePassword" name="password" required>
                            <div class="invalid-feedback" id="passwordError"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Employee</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editEmployeeForm" onsubmit="return false;">
                        <input type="hidden" id="editEmployeeId" name="id">
                        <div class="mb-3">
                            <label for="editEmployeeName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="editEmployeeName" name="name" required>
                            <div class="invalid-feedback" id="editNameError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeeUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="editEmployeeUsername" name="username" required>
                            <div class="invalid-feedback" id="editUsernameError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeePassword" class="form-label">Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="editEmployeePassword" name="password">
                            <div class="invalid-feedback" id="editPasswordError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeeStatus" class="form-label">Status</label>
                            <select class="form-select" id="editEmployeeStatus" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/employee.js"></script>

    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Function to clean up modal artifacts
        function cleanupModalArtifacts() {
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('padding-right');
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
        }

        // Add event listeners for modal cleanup
        const addEmployeeModal = document.getElementById('addEmployeeModal');

        addEmployeeModal.addEventListener('hidden.bs.modal', function () {
            cleanupModalArtifacts();
        });
    </script>
</body>
</html>
