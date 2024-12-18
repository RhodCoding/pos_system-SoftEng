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

$page_title = 'User Management';
include_once '../../includes/header.php';

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    // Prevent self-deletion
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account";
    } else {
        // Check if user has any orders
        $check_query = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $count = mysqli_fetch_assoc($result)['count'];

        if ($count > 0) {
            $_SESSION['error'] = "Cannot delete user: They have processed orders";
        } else {
            $query = "DELETE FROM users WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = "User deleted successfully";
            } else {
                $_SESSION['error'] = "Error deleting user";
            }
        }
    }
    redirect('admin/users/index.php');
}

// Get all users with their roles and activity
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM orders WHERE user_id = u.user_id) as order_count,
          (SELECT MAX(order_date) FROM orders WHERE user_id = u.user_id) as last_activity
          FROM users u 
          ORDER BY u.username";
$result = mysqli_query($conn, $query);
?>

<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Users</h5>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add New User
            </a>
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
                <table class="table table-bordered table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Orders</th>
                            <th>Last Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['full_name']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role'] == 'admin' ? 'danger' : 'info'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['order_count'] > 0): ?>
                                        <span class="badge badge-primary">
                                            <?php echo $user['order_count']; ?> orders
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">No orders</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    echo $user['last_activity'] 
                                        ? date('Y-m-d H:i', strtotime($user['last_activity']))
                                        : 'Never';
                                    ?>
                                </td>
                                <td>
                                    <a href="edit.php?id=<?php echo $user['user_id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if ($user['user_id'] != $_SESSION['user_id'] && $user['order_count'] == 0): ?>
                                        <form action="" method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this user?');">
                                            <input type="hidden" name="user_id" 
                                                   value="<?php echo $user['user_id']; ?>">
                                            <button type="submit" name="delete_user" 
                                                    class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
    $('#usersTable').DataTable({
        "order": [[2, "asc"], [1, "asc"]],
        "pageLength": 25
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?>
