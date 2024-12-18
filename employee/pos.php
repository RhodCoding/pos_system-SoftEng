<?php
session_start();

// Add authentication check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale - Bakery POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        .product-item {
            cursor: pointer;
            transition: transform 0.2s;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .product-item:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .cart-container {
            height: calc(100vh - 280px);
            overflow-y: auto;
        }
        .product-image {
            height: 120px;
            object-fit: cover;
            width: 100%;
        }
        .cashier-header {
            background: #2c3e50;
            color: white;
            padding: 1rem;
            margin-top: 56px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'components/navbar.php'; ?>

    <!-- Cashier Header -->
    <div class="cashier-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1 id="storeName" class="h3 mb-0">Bakery POS</h1>
                </div>
                <div class="col-auto">
                    <span class="h5 mb-0">Cashier: <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Unknown'; ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Products Section -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Products</h5>
                        <div class="input-group" style="max-width: 300px;">
                            <input type="text" class="form-control" placeholder="Search products..." id="searchProducts">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="product-grid">
                            <?php
                            require_once '../classes/Product.php';
                            $productModel = new Product();
                            $products = $productModel->getAll();
                            
                            foreach ($products as $product) {
                                $imagePath = "../assets/images/products/pandesal.png"; // Using pandesal.png for all products
                                ?>
                                <div class="product-item" onclick="addToCart(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                    <div class="p-2">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <p class="mb-1">₱<?php echo number_format($product['price'], 2); ?></p>
                                        <small class="text-muted">Stock: <?php echo isset($product['stock_quantity']) ? $product['stock_quantity'] : 0; ?></small>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cart -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Current Order</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="cart-container">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="cartItems">
                                    <!-- Cart items will be inserted here -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Cart Summary -->
                        <div class="p-3 border-top">
                            <!-- Discount Code -->
                            <div class="mb-3">
                                <label for="discountCode" class="form-label">Discount Code</label>
                                <input type="text" class="form-control" id="discountCode" placeholder="Enter discount code">
                            </div>

                            <!-- Totals -->
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span id="subtotal">₱0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Discount:</span>
                                <span id="discount">₱0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="fw-bold">Total:</span>
                                <span id="total" class="fw-bold">₱0.00</span>
                            </div>

                            <button id="checkoutBtn" class="btn btn-success w-100" disabled>
                                <i class="bi bi-cart-check"></i> Checkout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Process Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="paymentAmount" class="form-label">Amount Paid</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" id="paymentAmount" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Change</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="text" class="form-control" id="change" readonly>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="processPaymentBtn">Process Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/utils.js"></script>
    <script src="../assets/js/pos.js"></script>
</body>
</html>
