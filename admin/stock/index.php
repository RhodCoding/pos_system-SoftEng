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

$page_title = 'Stock Management';
include_once '../../includes/header.php';

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adjust_stock'])) {
    $product_id = (int)$_POST['product_id'];
    $adjustment = (int)$_POST['adjustment'];
    $reason = clean($_POST['reason']);
    
    try {
        mysqli_begin_transaction($conn);

        // Update product stock
        $query = "UPDATE products SET stock = stock + ? WHERE product_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $adjustment, $product_id);
        mysqli_stmt_execute($stmt);

        // Log stock adjustment
        $query = "INSERT INTO stock_adjustments (product_id, adjustment, reason, user_id) 
                 VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iisi", $product_id, $adjustment, $reason, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);

        mysqli_commit($conn);
        $_SESSION['success'] = "Stock adjusted successfully";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Error adjusting stock: " . $e->getMessage();
    }
    
    redirect('admin/stock/index.php');
}

// Get products with low stock
$low_stock_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.category_id 
                   WHERE p.stock < 10 
                   ORDER BY p.stock ASC";
$low_stock_result = mysqli_query($conn, $low_stock_query);

// Get all products
$products_query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.category_id 
                  ORDER BY p.name";
$products_result = mysqli_query($conn, $products_query);
?>

<div class="container-fluid mt-4">
    <!-- Low Stock Alerts -->
    <?php if (mysqli_num_rows($low_stock_result) > 0): ?>
        <div class="alert alert-warning">
            <h5><i class="fas fa-exclamation-triangle"></i> Low Stock Alerts</h5>
            <div class="row">
                <?php while ($product = mysqli_fetch_assoc($low_stock_result)): ?>
                    <div class="col-md-3 mb-2">
                        <div class="card border-warning">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1"><?php echo $product['name']; ?></h6>
                                <p class="card-text mb-1">
                                    Current Stock: 
                                    <span class="badge badge-danger"><?php echo $product['stock']; ?></span>
                                </p>
                                <button type="button" class="btn btn-warning btn-sm" 
                                        onclick="openStockModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                    Adjust Stock
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Stock Management Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Stock Management</h5>
            <div>
                <button type="button" class="btn btn-primary" onclick="exportStockReport()">
                    <i class="fas fa-download"></i> Export Stock Report
                </button>
            </div>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="stockTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
                            <tr>
                                <td><?php echo $product['name']; ?></td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $product['stock'] < 10 ? 'danger' : 'success'; ?>">
                                        <?php echo $product['stock']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($product['updated_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-info btn-sm" 
                                            onclick="openStockModal(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                        <i class="fas fa-edit"></i> Adjust Stock
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm" 
                                            onclick="viewStockHistory(<?php echo $product['product_id']; ?>)">
                                        <i class="fas fa-history"></i> History
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Stock Adjustment Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stock</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="product_id" id="modalProductId">
                    <h6 id="modalProductName"></h6>
                    <p>Current Stock: <span id="modalCurrentStock"></span></p>
                    
                    <div class="form-group">
                        <label>Adjustment</label>
                        <input type="number" name="adjustment" class="form-control" required>
                        <small class="form-text text-muted">
                            Use positive numbers to add stock, negative to remove
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea name="reason" class="form-control" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="adjust_stock" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openStockModal(product) {
    $('#modalProductId').val(product.product_id);
    $('#modalProductName').text(product.name);
    $('#modalCurrentStock').text(product.stock);
    $('#stockModal').modal('show');
}

function viewStockHistory(productId) {
    window.location.href = 'history.php?id=' + productId;
}

function exportStockReport() {
    window.location.href = 'export.php';
}

// Initialize DataTable
$(document).ready(function() {
    $('#stockTable').DataTable({
        "order": [[2, "asc"]], // Sort by stock level
        "pageLength": 25
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?>
