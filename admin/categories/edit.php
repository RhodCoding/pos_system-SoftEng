<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Get category ID from URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get category details
$query = "SELECT * FROM categories WHERE category_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $category_id);
mysqli_stmt_execute($stmt);
$category = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$category) {
    $_SESSION['error'] = "Category not found";
    redirect('admin/categories/index.php');
}

$page_title = 'Edit Category: ' . $category['name'];
include_once '../../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = clean($_POST['name']);
    $description = clean($_POST['description']);
    
    $errors = [];

    // Validate input
    if (empty($name)) {
        $errors[] = "Category name is required";
    }

    // Check if category name already exists (excluding current category)
    $check_query = "SELECT category_id FROM categories WHERE name = ? AND category_id != ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "si", $name, $category_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors[] = "Category name already exists";
    }

    // If no errors, update category
    if (empty($errors)) {
        $query = "UPDATE categories SET name = ?, description = ? WHERE category_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssi", $name, $description, $category_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Category updated successfully";
            redirect('admin/categories/index.php');
        } else {
            $errors[] = "Error updating category: " . mysqli_error($conn);
        }
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Edit Category: <?php echo $category['name']; ?></h5>
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

                    <form action="" method="POST">
                        <div class="form-group">
                            <label>Category Name *</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?php echo $_POST['name'] ?? $category['name']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" 
                                      rows="3"><?php echo $_POST['description'] ?? $category['description']; ?></textarea>
                        </div>

                        <!-- Show number of products in this category -->
                        <?php
                        $products_query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
                        $stmt = mysqli_prepare($conn, $products_query);
                        mysqli_stmt_bind_param($stmt, "i", $category_id);
                        mysqli_stmt_execute($stmt);
                        $product_count = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['count'];
                        ?>
                        <div class="form-group">
                            <div class="alert alert-info">
                                This category contains <?php echo $product_count; ?> product(s).
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Category
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($product_count > 0): ?>
                <!-- Display products in this category -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Products in this Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $products_query = "SELECT * FROM products WHERE category_id = ? ORDER BY name";
                                    $stmt = mysqli_prepare($conn, $products_query);
                                    mysqli_stmt_bind_param($stmt, "i", $category_id);
                                    mysqli_stmt_execute($stmt);
                                    $products_result = mysqli_stmt_get_result($stmt);
                                    
                                    while ($product = mysqli_fetch_assoc($products_result)):
                                    ?>
                                        <tr>
                                            <td><?php echo $product['name']; ?></td>
                                            <td><?php echo formatCurrency($product['price']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $product['stock'] < 10 ? 'danger' : 'success'; ?>">
                                                    <?php echo $product['stock']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="../products/edit.php?id=<?php echo $product['product_id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
