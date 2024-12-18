<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get product details
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          WHERE p.product_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    $_SESSION['error'] = "Product not found";
    redirect('index.php');
}

$page_title = 'Stock History: ' . $product['name'];
include_once '../../includes/header.php';

// Get stock adjustment history
$query = "SELECT sa.*, u.username 
          FROM stock_adjustments sa 
          LEFT JOIN users u ON sa.user_id = u.user_id 
          WHERE sa.product_id = ? 
          ORDER BY sa.created_at DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$history_result = mysqli_stmt_get_result($stmt);
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                Stock History: <?php echo $product['name']; ?>
                <a href="index.php" class="btn btn-secondary float-right">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </h5>
        </div>
        <div class="card-body">
            <!-- Product Info -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>Product Details</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>Category:</th>
                            <td><?php echo $product['category_name']; ?></td>
                        </tr>
                        <tr>
                            <th>Current Stock:</th>
                            <td>
                                <span class="badge badge-<?php echo $product['stock'] < 10 ? 'danger' : 'success'; ?>">
                                    <?php echo $product['stock']; ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td><?php echo date('Y-m-d H:i', strtotime($product['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- History Table -->
            <h6>Adjustment History</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Adjustment</th>
                            <th>Reason</th>
                            <th>Adjusted By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($history_result)): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $row['adjustment'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $row['adjustment'] > 0 ? '+' : ''; ?><?php echo $row['adjustment']; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['reason']; ?></td>
                                <td><?php echo $row['username']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if (mysqli_num_rows($history_result) == 0): ?>
                            <tr>
                                <td colspan="4" class="text-center">No adjustment history found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
