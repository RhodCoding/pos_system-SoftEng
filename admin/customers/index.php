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

$page_title = 'Customer Management';
include_once '../../includes/header.php';

// Handle customer deletion
if (isset($_POST['delete_customer'])) {
    $customer_id = (int)$_POST['customer_id'];
    
    // Check if customer has any orders
    $check_query = "SELECT COUNT(*) as count FROM orders WHERE customer_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $customer_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count = mysqli_fetch_assoc($result)['count'];

    if ($count > 0) {
        $_SESSION['error'] = "Cannot delete customer: They have existing orders";
    } else {
        $query = "DELETE FROM customers WHERE customer_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $customer_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Customer deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting customer";
        }
    }
    redirect('index.php');
}

// Get all customers with their order statistics
$query = "SELECT c.*, 
          COUNT(o.order_id) as total_orders,
          SUM(o.total_amount) as total_spent,
          MAX(o.order_date) as last_order
          FROM customers c 
          LEFT JOIN orders o ON c.customer_id = o.customer_id 
          GROUP BY c.customer_id 
          ORDER BY c.name";
$result = mysqli_query($conn, $query);
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Customers</h5>
            <div>
                <a href="export.php" class="btn btn-success mr-2">
                    <i class="fas fa-download"></i> Export
                </a>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add New Customer
                </a>
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
                <table class="table table-bordered table-hover" id="customersTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Total Orders</th>
                            <th>Total Spent</th>
                            <th>Last Order</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($customer = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $customer['name']; ?></td>
                                <td><?php echo $customer['phone']; ?></td>
                                <td><?php echo $customer['email']; ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $customer['total_orders']; ?> orders
                                    </span>
                                </td>
                                <td><?php echo formatCurrency($customer['total_spent']); ?></td>
                                <td>
                                    <?php
                                    echo $customer['last_order'] 
                                        ? date('Y-m-d', strtotime($customer['last_order']))
                                        : 'Never';
                                    ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $customer['active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $customer['active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo $customer['customer_id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if ($customer['total_orders'] == 0): ?>
                                        <form action="" method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                            <input type="hidden" name="customer_id" 
                                                   value="<?php echo $customer['customer_id']; ?>">
                                            <button type="submit" name="delete_customer" 
                                                    class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="view.php?id=<?php echo $customer['customer_id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> View
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

<script>
$(document).ready(function() {
    $('#customersTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25,
        "columns": [
            null,
            null,
            null,
            { "type": "num" },
            { "type": "num" },
            { "type": "date" },
            null,
            { "orderable": false }
        ]
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?>
