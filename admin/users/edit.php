<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get user details
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {
    $_SESSION['error'] = "User not found";
    redirect('/index.php');
}

$page_title = 'Edit User: ' . $user['username'];
include_once '../../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean($_POST['username']);
    $full_name = clean($_POST['full_name']);
    $role = clean($_POST['role']);
    $active = isset($_POST['active']) ? 1 : 0;
    $password = $_POST['password'];
    
    $errors = [];

    // Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required";
    } else {
        // Check if username already exists (excluding current user)
        $check_query = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "si", $username, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Username already exists";
        }
    }

    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }

    if (!in_array($role, ['admin', 'cashier'])) {
        $errors[] = "Invalid role selected";
    }

    // Check if trying to remove last admin
    if ($user['role'] == 'admin' && $role != 'admin') {
        $admin_count_query = "SELECT COUNT(*) as count FROM users WHERE role = 'admin' AND user_id != ?";
        $stmt = mysqli_prepare($conn, $admin_count_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        
        if ($result['count'] == 0) {
            $errors[] = "Cannot remove the last administrator";
        }
    }

    // If password is provided, validate it
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        } elseif ($password !== $_POST['confirm_password']) {
            $errors[] = "Passwords do not match";
        }
    }

    // If no errors, update user
    if (empty($errors)) {
        if (!empty($password)) {
            // Update with new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users 
                     SET username = ?, password = ?, full_name = ?, role = ?, active = ? 
                     WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssssii", $username, $hashed_password, 
                                 $full_name, $role, $active, $user_id);
        } else {
            // Update without changing password
            $query = "UPDATE users 
                     SET username = ?, full_name = ?, role = ?, active = ? 
                     WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssii", $username, $full_name, 
                                 $role, $active, $user_id);
        }

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "User updated successfully";
            redirect('admin/users/index.php');
        } else {
            $errors[] = "Error updating user: " . mysqli_error($conn);
        }
    }
}

// Get user activity statistics
$stats_query = "SELECT 
                  COUNT(*) as total_orders,
                  SUM(total_amount) as total_sales,
                  MAX(order_date) as last_order
                FROM orders 
                WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit User: <?php echo $user['username']; ?></h5>
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

                    <form action="" method="POST" id="editUserForm">
                        <div class="form-group">
                            <label>Username *</label>
                            <input type="text" name="username" class="form-control" 
                                   value="<?php echo $_POST['username'] ?? $user['username']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?php echo $_POST['full_name'] ?? $user['full_name']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Role *</label>
                            <select name="role" class="form-control" required>
                                <option value="cashier" <?php echo ($user['role'] == 'cashier') ? 'selected' : ''; ?>>
                                    Cashier
                                </option>
                                <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>
                                    Administrator
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" name="active" 
                                       class="custom-control-input" id="activeSwitch" 
                                       <?php echo $user['active'] ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="activeSwitch">
                                    Active Account
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="password" class="form-control" minlength="6">
                            <small class="form-text text-muted">
                                Leave blank to keep current password
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update User
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
            <!-- User Statistics -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">User Statistics</h5>
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
                            Total Sales
                            <span class="badge badge-success badge-pill">
                                <?php echo formatCurrency($stats['total_sales']); ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Last Order
                            <span class="badge badge-info badge-pill">
                                <?php echo $stats['last_order'] ? date('Y-m-d H:i', strtotime($stats['last_order'])) : 'Never'; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Account Created
                            <span class="badge badge-secondary badge-pill">
                                <?php echo date('Y-m-d', strtotime($user['created_at'])); ?>
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
    // Password match validation
    $('#editUserForm').submit(function(e) {
        var password = $('input[name="password"]').val();
        var confirm = $('input[name="confirm_password"]').val();
        
        if (password && password !== confirm) {
            e.preventDefault();
            alert('Passwords do not match!');
        }
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?>
