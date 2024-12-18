<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

$page_title = 'Add New User';
include_once '../../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = clean($_POST['full_name']);
    $role = clean($_POST['role']);
    $active = isset($_POST['active']) ? 1 : 0;
    
    $errors = [];

    // Validate inputs
    if (empty($username)) {
        $errors[] = "Username is required";
    } else {
        // Check if username already exists
        $check_query = "SELECT user_id FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Username already exists";
        }
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }

    if (!in_array($role, ['admin', 'cashier'])) {
        $errors[] = "Invalid role selected";
    }

    // If no errors, insert user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO users (username, password, full_name, role, active) 
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssi", $username, $hashed_password, 
                             $full_name, $role, $active);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "User added successfully";
            redirect('admin/users/index.php');
        } else {
            $errors[] = "Error adding user: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add New User</h5>
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

                    <form action="" method="POST" id="addUserForm">
                        <div class="form-group">
                            <label>Username *</label>
                            <input type="text" name="username" class="form-control" 
                                   value="<?php echo $_POST['username'] ?? ''; ?>" required>
                            <small class="form-text text-muted">
                                Username must be unique and will be used for login
                            </small>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Password *</label>
                                <input type="password" name="password" class="form-control" 
                                       minlength="6" required>
                                <small class="form-text text-muted">
                                    Minimum 6 characters
                                </small>
                            </div>

                            <div class="form-group col-md-6">
                                <label>Confirm Password *</label>
                                <input type="password" name="confirm_password" 
                                       class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?php echo $_POST['full_name'] ?? ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Role *</label>
                            <select name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="cashier" <?php echo (isset($_POST['role']) && $_POST['role'] == 'cashier') ? 'selected' : ''; ?>>
                                    Cashier
                                </option>
                                <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>
                                    Administrator
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" name="active" 
                                       class="custom-control-input" id="activeSwitch" 
                                       <?php echo (!isset($_POST['active']) || $_POST['active']) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="activeSwitch">
                                    Active Account
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save User
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Password match validation
    $('#addUserForm').submit(function(e) {
        var password = $('input[name="password"]').val();
        var confirm = $('input[name="confirm_password"]').val();
        
        if (password !== confirm) {
            e.preventDefault();
            alert('Passwords do not match!');
        }
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?>
