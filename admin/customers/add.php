<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

$page_title = 'Add New Customer';
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

    // Check if phone number already exists
    if (!empty($phone)) {
        $check_query = "SELECT customer_id FROM customers WHERE phone = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $phone);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Phone number already exists";
        }
    }

    // Check if email already exists
    if (!empty($email)) {
        $check_query = "SELECT customer_id FROM customers WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = "Email already exists";
        }
    }

    // If no errors, insert customer
    if (empty($errors)) {
        $query = "INSERT INTO customers (name, phone, email, address, notes, active) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssi", $name, $phone, $email, $address, $notes, $active);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Customer added successfully";
            redirect('index.php');
        } else {
            $errors[] = "Error adding customer: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add New Customer</h5>
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

                    <form action="" method="POST" id="addCustomerForm">
                        <div class="form-group">
                            <label>Name *</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?php echo $_POST['name'] ?? ''; ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Phone</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo $_POST['phone'] ?? ''; ?>">
                                <small class="form-text text-muted">
                                    Either phone or email is required
                                </small>
                            </div>

                            <div class="form-group col-md-6">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?php echo $_POST['email'] ?? ''; ?>">
                                <small class="form-text text-muted">
                                    Either phone or email is required
                                </small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" 
                                      rows="2"><?php echo $_POST['address'] ?? ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" 
                                      rows="3"><?php echo $_POST['notes'] ?? ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" name="active" 
                                       class="custom-control-input" id="activeSwitch" 
                                       <?php echo (!isset($_POST['active']) || $_POST['active']) ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="activeSwitch">
                                    Active Customer
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Customer
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
    // Form validation
    $('#addCustomerForm').submit(function(e) {
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
