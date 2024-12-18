<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../login.php');
}

$page_title = 'POS';
include_once '../includes/header.php';

// Get all categories
$query = "SELECT * FROM categories WHERE active = 1 ORDER BY name";
$categories = mysqli_query($conn, $query);

// Get all products
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          WHERE p.active = 1 AND p.stock > 0 
          ORDER BY p.name";
$products = mysqli_query($conn, $query);
?>

<div class="container-fluid mt-3">
    <div class="row">
        <!-- Products Section (Right) -->
        <div class="col-md-8">
            <!-- Category Filters -->
            <div class="mb-3">
                <button class="btn btn-outline-primary category-filter active" data-category="all">
                    All Products
                </button>
                <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                    <button class="btn btn-outline-primary category-filter" 
                            data-category="<?php echo $category['category_id']; ?>">
                        <?php echo $category['name']; ?>
                    </button>
                <?php endwhile; ?>
            </div>

            <!-- Products Grid -->
            <div class="row" id="products-grid">
                <?php while ($product = mysqli_fetch_assoc($products)): ?>
                    <div class="col-md-3 mb-3 product-item" 
                         data-category="<?php echo $product['category_id']; ?>">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <h6 class="card-title mb-1"><?php echo $product['name']; ?></h6>
                                <p class="card-text text-muted small mb-2">
                                    <?php echo $product['category_name']; ?>
                                </p>
                                <p class="card-text font-weight-bold mb-2">
                                    <?php echo formatCurrency($product['price']); ?>
                                </p>
                                <p class="card-text small text-<?php echo $product['stock'] < 10 ? 'danger' : 'success'; ?>">
                                    Stock: <?php echo $product['stock']; ?>
                                </p>
                                <button class="btn btn-primary btn-sm add-to-cart" 
                                        data-id="<?php echo $product['product_id']; ?>"
                                        data-name="<?php echo $product['name']; ?>"
                                        data-price="<?php echo $product['price']; ?>"
                                        data-stock="<?php echo $product['stock']; ?>">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Cart Section (Left) -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Current Order</h5>
                </div>
                <div class="card-body p-0">
                    <!-- Cart Items -->
                    <div class="cart-items">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Price</th>
                                    <th class="text-right">Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="cart-items">
                                <!-- Cart items will be inserted here via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Cart Summary -->
                    <div class="card-body border-top">
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="mb-0">Subtotal:</h6>
                            <h6 class="mb-0" id="subtotal">₱0.00</h6>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <h6 class="mb-0">Tax (<?php echo getSetting('tax_rate'); ?>%):</h6>
                            <h6 class="mb-0" id="tax">₱0.00</h6>
                        </div>
                        <div class="d-flex justify-content-between">
                            <h5 class="mb-0">Total:</h5>
                            <h5 class="mb-0" id="total">₱0.00</h5>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card-body border-top">
                        <button class="btn btn-danger btn-block mb-2" id="clear-cart">
                            <i class="fas fa-trash"></i> Clear Cart
                        </button>
                        <button class="btn btn-success btn-block" id="checkout">
                            <i class="fas fa-cash-register"></i> Process Payment
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
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <div class="form-group">
                        <label>Total Amount</label>
                        <input type="text" class="form-control form-control-lg" id="payment-total" readonly>
                    </div>
                    <div class="form-group">
                        <label>Received Amount</label>
                        <input type="number" class="form-control form-control-lg" id="received-amount" 
                               step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Change</label>
                        <input type="text" class="form-control form-control-lg" id="change-amount" readonly>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select class="form-control" id="payment-method" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="process-payment">
                    Complete Payment
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Cart management
let cart = [];
const taxRate = parseFloat(<?php echo getSetting('tax_rate', '12'); ?>) / 100;

// Add to cart function
function addToCart(product) {
    // Check if product already exists in cart
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
        // Check stock limit
        if (existingItem.quantity >= existingItem.stock) {
            alert('Cannot add more items. Stock limit reached.');
            return;
        }
        existingItem.quantity += 1;
    } else {
        cart.push({ ...product });
    }
    
    updateCartDisplay();
}

// Remove from cart
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCartDisplay();
}

// Update quantity
function updateQuantity(productId, newQty) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        // Check stock limit
        if (newQty > item.stock) {
            alert('Cannot add more items. Stock limit reached.');
            return;
        }
        if (newQty < 1) {
            removeFromCart(productId);
            return;
        }
        item.quantity = newQty;
        updateCartDisplay();
    }
}

// Update cart display
function updateCartDisplay() {
    const cartBody = $('#cart-items');
    cartBody.empty();
    
    let subtotal = 0;
    
    cart.forEach(item => {
        const total = item.price * item.quantity;
        subtotal += total;
        
        cartBody.append(`
            <tr>
                <td>${item.name}</td>
                <td class="text-center">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <button class="btn btn-outline-secondary" 
                                    onclick="updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                        </div>
                        <input type="number" class="form-control text-center" 
                               value="${item.quantity}" min="1" max="${item.stock}"
                               onchange="updateQuantity(${item.id}, parseInt(this.value))">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" 
                                    onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                        </div>
                    </div>
                </td>
                <td class="text-right">${formatCurrency(item.price)}</td>
                <td class="text-right">${formatCurrency(total)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-danger" 
                            onclick="removeFromCart(${item.id})">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `);
    });
    
    const tax = subtotal * taxRate;
    const total = subtotal + tax;
    
    $('#subtotal').text(formatCurrency(subtotal));
    $('#tax').text(formatCurrency(tax));
    $('#total').text(formatCurrency(total));
    
    // Enable/disable checkout button
    $('#checkout').prop('disabled', cart.length === 0);
}

// Format currency
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toFixed(2);
}

// Category filter
$('.category-filter').click(function() {
    $('.category-filter').removeClass('active');
    $(this).addClass('active');
    
    const categoryId = $(this).data('category');
    
    if (categoryId === 'all') {
        $('.product-item').show();
    } else {
        $('.product-item').hide();
        $(`.product-item[data-category="${categoryId}"]`).show();
    }
});

// Clear cart
$('#clear-cart').click(function() {
    if (confirm('Are you sure you want to clear the cart?')) {
        cart = [];
        updateCartDisplay();
    }
});

// Payment processing
$('#checkout').click(function() {
    if (cart.length === 0) return;
    
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = total * taxRate;
    const finalTotal = total + tax;
    
    $('#payment-total').val(formatCurrency(finalTotal));
    $('#received-amount').val('').focus();
    $('#change-amount').val('');
    $('#paymentModal').modal('show');
});

// Calculate change
$('#received-amount').on('input', function() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = total * taxRate;
    const finalTotal = total + tax;
    const received = parseFloat($(this).val()) || 0;
    const change = received - finalTotal;
    
    $('#change-amount').val(change >= 0 ? formatCurrency(change) : '');
    $('#process-payment').prop('disabled', change < 0);
});

// Process payment
$('#process-payment').click(function() {
    const formData = {
        cart: cart,
        payment_method: $('#payment-method').val(),
        received_amount: parseFloat($('#received-amount').val()),
        total_amount: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
        tax_amount: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) * taxRate
    };
    
    // Send to process.php
    $.post('process.php', formData)
        .done(function(response) {
            if (response.success) {
                // Open receipt in new window
                window.open('receipt.php?order_id=' + response.order_id, '_blank');
                // Clear cart and close modal
                cart = [];
                updateCartDisplay();
                $('#paymentModal').modal('hide');
            } else {
                alert('Error: ' + response.message);
            }
        })
        .fail(function() {
            alert('Error processing payment. Please try again.');
        });
});

// Initialize cart display
updateCartDisplay();
</script>

<?php include_once '../includes/footer.php'; ?>
