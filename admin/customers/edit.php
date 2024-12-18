<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Get customer ID from URL
$customer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get customer details
$query = "SELECT * FROM customers WHERE customer_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$customer) {
    $_SESSION['error'] = "Customer not found";
    redirect('index.php');
}

$page_title = 'Edit Customer: ' . $customer['name'];
include_once '../../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = clean($_POST['name']);
    $phone = clean($_POST['phone']);
    $email = clean($_POST['email']);
    $address = clean($_POST['address']);
    $notes = clean($_POST['notes']);
    $active = isset($_POST['active']) ? 1 : 0;
    
    $errors = [];

    // Validate inputs
    if (empty($name)) {
        $errors[] = "Name is required";
    }

    if (empty($phone) && empty($email)) {
        $errors[] = "Either phone or email is required";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Check if phone number already exists (excluding current customer)
    if (!empty($phone)) {
        $check_query = "SELECT customer_id FROM customers WHERE phone = ? AND customer_id != ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "si", $phone, $customer_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Phone number already exists";
        }
    }

    // Check if email already exists (excluding current customer)
    if (!empty($email)) {
        $check_query = "SELECT customer_id FROM customers WHERE email = ? AND customer_id != ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "si", $email, $customer_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email already exists";
        }
    }

    // If no errors, update customer
    if (empty($errors)) {
        $query = "UPDATE customers 
                 SET name = ?, phone = ?, email = ?, address = ?, notes = ?, active = ? 
                 WHERE customer_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssii", $name, $phone, $email, $address, 
                             $notes, $active, $customer_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Customer updated successfully";
            redirect('index.php');
        } else {
            $errors[] = "Error updating customer: " . mysqli_error($conn);
        }
    }
}

// Get customer statistics
$stats_query = "SELECT 
                  COUNT(*) as total_orders,
                  SUM(total_amount) as total_spent,
                  MAX(order_date) as last_order,
                  MIN(order_date) as first_order,
                  AVG(total_amount) as avg_order
                FROM orders 
                WHERE customer_id = ?";
$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Customer</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" id="editCustomerForm">
                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?php echo $_POST['name'] ?? $customer['name']; ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Phone</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo $_POST['phone'] ?? $customer['phone']; ?>">
                                <small class="form-text text-muted">
                                    Either phone or email is required
                                </small>
                            </div>

                            <div class="form-group col-md-6">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo $_POST['email'] ?? $customer['email']; ?>">
                                <small class="form-text text-muted">
                                    Either phone or email is required
                                </small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" 
                                      rows="2"><?php echo $_POST['address'] ?? $customer['address']; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" 
                                      rows="3"><?php echo $_POST['notes'] ?? $customer['notes']; ?></textarea>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" name="active" 
                                       class="custom-control-input" id="activeSwitch" 
                                       <?php echo (isset($_POST['active']) ? $_POST['active'] : $customer['active']) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="activeSwitch">
                                    Active Customer
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Customer
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Customer Statistics -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Customer Statistics</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Orders
                            <span class="badge badge-primary badge-pill">
                                <?php echo $stats['total_orders']; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Spent
                            <span class="badge badge-success badge-pill">
                                <?php echo formatCurrency($stats['total_spent']); ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Average Order
                            <span class="badge badge-info badge-pill">
                                <?php echo formatCurrency($stats['avg_order']); ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            First Order
                            <span class="badge badge-secondary badge-pill">
                                <?php echo $stats['first_order'] ? date('Y-m-d', strtotime($stats['first_order'])) : 'Never'; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Last Order
                            <span class="badge badge-secondary badge-pill">
                                <?php echo $stats['last_order'] ? date('Y-m-d', strtotime($stats['last_order'])) : 'Never'; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Customer Since
                            <span class="badge badge-secondary badge-pill">
                                <?php echo date('Y-m-d', strtotime($customer['created_at'])); ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Form validation
    $('#editCustomerForm').submit(function(e) {
        var phone = $('input[name="phone"]').val();
        var email = $('input[name="email"]').val();
        
        if (!phone && !email) {
            e.preventDefault();
            alert('Either phone or email is required!');
        }
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?>
