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

$page_title = 'Category Management';
include_once '../../includes/header.php';

// Handle category deletion
if (isset($_POST['delete_category'])) {
    $category_id = (int)$_POST['category_id'];
    
    // Check if category has products
    $check_query = "SELECT COUNT(*) as count FROM products WHERE category_id = ?";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $count = mysqli_fetch_assoc($result)['count'];

    if ($count > 0) {
        $_SESSION['error'] = "Cannot delete category: It contains products";
    } else {
        $query = "DELETE FROM categories WHERE category_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Category deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting category";
        }
    }
    redirect('admin/categories/index.php');
}

// Get all categories with product counts
$query = "SELECT c.*, COUNT(p.product_id) as product_count 
          FROM categories c 
          LEFT JOIN products p ON c.category_id = p.category_id 
          GROUP BY c.category_id 
          ORDER BY c.name";
$result = mysqli_query($conn, $query);
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Categories</h5>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Category
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
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($category = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo $category['category_id']; ?></td>
                                        <td><?php echo $category['name']; ?></td>
                                        <td><?php echo $category['description'] ?? '-'; ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo $category['product_count']; ?> products
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit.php?id=<?php echo $category['category_id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <?php if ($category['product_count'] == 0): ?>
                                                <form action="" method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                    <input type="hidden" name="category_id" 
                                                           value="<?php echo $category['category_id']; ?>">
                                                    <button type="submit" name="delete_category" 
                                                            class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <?php if (mysqli_num_rows($result) == 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No categories found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
