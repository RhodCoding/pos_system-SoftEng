<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

$page_title = 'Add New Product';
include_once '../../includes/header.php';

// Get all categories for the dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = clean($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $description = clean($_POST['description']);
    
    $errors = [];

    // Validate inputs
    if (empty($name)) {
        $errors[] = "Product name is required";
    }
    if ($price <= 0) {
        $errors[] = "Price must be greater than zero";
    }
    if ($stock < 0) {
        $errors[] = "Stock cannot be negative";
    }

    // Handle image upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        if (!in_array(strtolower($filetype), $allowed)) {
            $errors[] = "Only JPG, JPEG, PNG and GIF files are allowed";
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = "../../uploads/products/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Generate unique filename
            $new_filename = uniqid() . '.' . $filetype;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_path = '/uploads/products/' . $new_filename;
            } else {
                $errors[] = "Error uploading image";
            }
        }
    }

    // If no errors, insert product
    if (empty($errors)) {
        $query = "INSERT INTO products (name, category_id, price, stock, description, image_path) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sidiss", $name, $category_id, $price, $stock, $description, $image_path);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Product added successfully";
            redirect('admin/products/index.php');
        } else {
            $errors[] = "Error adding product: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Add New Product</h5>
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

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?php echo $_POST['name'] ?? ''; ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?php echo $category['category_id']; ?>"
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo $category['name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Price *</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">₱</span>
                                    </div>
                                    <input type="number" name="price" class="form-control" 
                                           step="0.01" min="0" 
                                           value="<?php echo $_POST['price'] ?? ''; ?>" required>
                                </div>
                            </div>

                            <div class="form-group col-md-6">
                                <label>Initial Stock *</label>
                                <input type="number" name="stock" class="form-control" 
                                       min="0" value="<?php echo $_POST['stock'] ?? '0'; ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo $_POST['description'] ?? ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Product Image</label>
                            <div class="custom-file">
                                <input type="file" name="image" class="custom-file-input" id="productImage">
                                <label class="custom-file-label" for="productImage">Choose file</label>
                            </div>
                            <small class="form-text text-muted">
                                Allowed formats: JPG, JPEG, PNG, GIF. Max size: 2MB
                            </small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Product
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
// Update file input label with selected filename
document.querySelector('.custom-file-input').addEventListener('change', function(e) {
    var fileName = e.target.files[0].name;
    var label = e.target.nextElementSibling;
    label.innerHTML = fileName;
});
</script>

<?php include_once '../../includes/footer.php'; ?>
