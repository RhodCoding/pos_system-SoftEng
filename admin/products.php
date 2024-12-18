<?php
require_once '../includes/session.php';
require_once '../classes/Product.php';
require_once '../classes/Category.php';

// Initialize Product class
$product = new Product();
$category = new Category();

// Get all products
$products = $product->findAll();
$categories = $category->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Bakery POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .product-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .product-image-cell {
            width: 100px;
            text-align: center;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block sidebar">
                <?php include 'components/sidebar.php'; ?>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3">
                    <h2>Products</h2>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus"></i> Add Product
                    </button>
                </div>

                <div class="row mb-3">
                    <div class="col-md-8">
                        <input type="text" class="form-control" id="searchProducts" placeholder="Search products...">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="categoryFilter">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['id']) ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['id']) ?></td>
                                    <td class="product-image-cell">
                                        <?php if ($product['image'] && file_exists("../uploads/products/" . $product['image'])): ?>
                                            <img src="../uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                                                 alt="<?= htmlspecialchars($product['name']) ?>" 
                                                 class="product-thumbnail">
                                        <?php else: ?>
                                            <img src="../assets/images/no-image.jpg" 
                                                 alt="No Image" 
                                                 class="product-thumbnail">
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($product['category']) ?></td>
                                    <td>₱<?= number_format($product['price'], 2) ?></td>
                                    <td><?= htmlspecialchars($product['stock']) ?></td>
                                    <td>
                                        <span class="badge <?= $product['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ucfirst(htmlspecialchars($product['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary edit-product" 
                                                data-id="<?= htmlspecialchars($product['id']) ?>">
                                            Edit
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-product" 
                                                data-id="<?= htmlspecialchars($product['id']) ?>">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['id']) ?>">
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="stock" name="stock" required>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="form-text">Recommended size: 80x80 pixels. Image will be automatically resized.</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveProduct">Save Product</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editProductForm" enctype="multipart/form-data">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category_id" class="form-label">Category</label>
                        <select class="form-select" id="edit_category_id" name="category_id" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['id']) ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_price" class="form-label">Price</label>
                        <input type="number" class="form-control" id="edit_price" name="price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_stock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="edit_stock" name="stock" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_image" class="form-label">Product Image</label>
                        <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                        <div class="form-text">Leave empty to keep current image. Recommended size: 80x80 pixels.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="updateProduct">Update Product</button>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Form submission for adding products
            $('#addProductForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: 'handlers/product_handler.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                alert(result.message);
                                location.reload();
                            } else {
                                alert(result.message || 'Failed to add product');
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            alert('Error processing server response');
                        }
                    },
                    error: function() {
                        alert('An error occurred while adding the product');
                    }
                });
            });

            // Handle edit button clicks
            $('.edit-product').click(function() {
                const productId = $(this).data('id');
                
                // Fetch product details
                $.get('handlers/get_product.php', { id: productId }, function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            const product = result.data;
                            
                            // Fill the form with product details
                            $('#edit_id').val(product.id);
                            $('#edit_name').val(product.name);
                            $('#edit_category_id').val(product.category_id);
                            $('#edit_price').val(product.price);
                            $('#edit_stock').val(product.stock);
                            $('#edit_status').val(product.status);
                            
                            // Show the modal
                            $('#editProductModal').modal('show');
                        } else {
                            alert(result.message || 'Failed to fetch product details');
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        alert('Error processing server response');
                    }
                }).fail(function() {
                    alert('Failed to fetch product details');
                });
            });

            // Handle update form submission
            $('#editProductForm').on('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: 'handlers/update_product.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                alert(result.message);
                                location.reload();
                            } else {
                                alert(result.message || 'Failed to update product');
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            alert('Error processing server response');
                        }
                    },
                    error: function() {
                        alert('An error occurred while updating the product');
                    }
                });
            });

            // Handle delete button clicks
            $('.delete-product').click(function() {
                const productId = $(this).data('id');
                const productName = $(this).closest('tr').find('td:nth-child(3)').text();
                
                if (confirm(`Are you sure you want to delete "${productName}"?`)) {
                    $.ajax({
                        url: 'handlers/delete_product.php',
                        type: 'POST',
                        data: { product_id: productId },
                        success: function(response) {
                            try {
                                const result = JSON.parse(response);
                                if (result.success) {
                                    alert(result.message);
                                    location.reload();
                                } else {
                                    alert(result.message || 'Failed to delete product');
                                }
                            } catch (e) {
                                console.error('Error parsing response:', e);
                                alert('Error processing server response');
                            }
                        },
                        error: function() {
                            alert('An error occurred while deleting the product');
                        }
                    });
                }
            });

            // Trigger form submissions when clicking save/update buttons
            $('#saveProduct').click(function() {
                $('#addProductForm').submit();
            });
            
            $('#updateProduct').click(function() {
                $('#editProductForm').submit();
            });
        });
    </script>

</body>
</html>
