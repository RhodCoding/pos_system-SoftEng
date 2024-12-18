// POS System Management
const POSManager = {
    // Sample products data (for frontend development)
    sampleProducts: [
        { id: 1, name: 'Pandesal', price: 5.00, stock: 100, image: 'assets/images/products/pandesal.png' },
        { id: 2, name: 'Ensaymada', price: 20.00, stock: 50, image: 'assets/images/products/pandesal.png' },
        { id: 3, name: 'Chocolate Cake', price: 450.00, stock: 5, image: 'assets/images/products/pandesal.png' },
        { id: 4, name: 'Cheese Bread', price: 15.00, stock: 30, image: 'assets/images/products/pandesal.png' },
        { id: 5, name: 'Ube Cake', price: 500.00, stock: 3, image: 'assets/images/products/pandesal.png' },
        { id: 6, name: 'Spanish Bread', price: 10.00, stock: 40, image: 'assets/images/products/pandesal.png' }
    ],

    // Default product image
    defaultProductImage: 'assets/images/products/pandesal.png',

    // Cart data
    cart: [],
    discountCode: null,
    discountAmount: 0,

    // Initialize POS system
    init() {
        this.loadProducts();
        this.setupEventListeners();
        this.updateCartSummary();
        this.loadSettings();
    },

    // Load system settings
    async loadSettings() {
        try {
            const settings = await Api.getSettings();
            this.settings = settings;
            // Update UI with store settings
            if (settings.store_name) {
                document.querySelector('#storeName').textContent = settings.store_name.value;
            }
        } catch (error) {
            console.error('Failed to load settings:', error);
        }
    },

    // Load products into the grid
    loadProducts() {
        const grid = document.querySelector('#productsGrid');
        grid.innerHTML = '';

        this.sampleProducts.forEach(product => {
            const productHtml = `
                <div class="card product-item" data-id="${product.id}">
                    <img src="${product.image}" 
                         class="product-image card-img-top" 
                         alt="${product.name}"
                         onerror="this.src='${this.defaultProductImage}'">
                    <div class="card-body text-center">
                        <h6 class="card-title">${product.name}</h6>
                        <p class="card-text">₱${product.price.toFixed(2)}</p>
                        <small class="text-muted">Stock: ${product.stock}</small>
                    </div>
                </div>
            `;
            grid.insertAdjacentHTML('beforeend', productHtml);
        });
    },

    // Add item to cart
    addToCart(productId) {
        const product = this.sampleProducts.find(p => p.id === productId);
        if (!product) return;

        const cartItem = this.cart.find(item => item.id === productId);
        if (cartItem) {
            if (cartItem.quantity < product.stock) {
                cartItem.quantity++;
                this.updateCartDisplay();
                this.showToast(`Added another ${product.name} to cart`);
            } else {
                this.showToast('Not enough stock!', 'danger');
            }
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1
            });
            this.updateCartDisplay();
            this.showToast(`Added ${product.name} to cart`);
        }
    },

    // Update cart display
    updateCartDisplay() {
        const cartBody = document.querySelector('#cartItems');
        cartBody.innerHTML = '';

        this.cart.forEach(item => {
            const total = item.price * item.quantity;
            const row = `
                <tr>
                    <td>${item.name}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary decrease-qty" data-id="${item.id}">-</button>
                            <span class="btn btn-outline-secondary disabled">${item.quantity}</span>
                            <button class="btn btn-outline-secondary increase-qty" data-id="${item.id}">+</button>
                        </div>
                    </td>
                    <td>₱${item.price.toFixed(2)}</td>
                    <td>₱${total.toFixed(2)}</td>
                    <td>
                        <button class="btn btn-sm btn-danger remove-item" data-id="${item.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            cartBody.insertAdjacentHTML('beforeend', row);
        });

        this.updateCartSummary();
    },

    // Update cart totals
    async updateCartSummary() {
        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        // If there's a discount code, validate it
        if (this.discountCode) {
            try {
                const discountResult = await Api.validateDiscount(this.discountCode, subtotal, this.cart);
                this.discountAmount = discountResult.amount;
            } catch (error) {
                console.error('Failed to validate discount:', error);
                this.discountAmount = 0;
                this.discountCode = null;
            }
        }

        const total = subtotal - this.discountAmount;
        
        document.querySelector('#subtotal').textContent = `₱${subtotal.toFixed(2)}`;
        document.querySelector('#discount').textContent = `₱${this.discountAmount.toFixed(2)}`;
        document.querySelector('#total').textContent = `₱${total.toFixed(2)}`;
    },

    // Process payment
    async processPayment() {
        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const total = subtotal - this.discountAmount;

        // Get payment amount from input
        const amountPaid = parseFloat(document.querySelector('#paymentAmount').value);
        
        if (isNaN(amountPaid) || amountPaid < total) {
            this.showToast('Invalid payment amount', 'error');
            return;
        }

        const change = amountPaid - total;

        try {
            const orderData = {
                items: this.cart,
                subtotal: subtotal,
                discount_code: this.discountCode,
                discount_amount: this.discountAmount,
                total: total,
                amount_paid: amountPaid,
                change: change
            };

            await Api.createOrder(orderData);
            
            document.querySelector('#change').textContent = `₱${change.toFixed(2)}`;
            this.showToast('Payment processed successfully');
            this.completeSale();
        } catch (error) {
            console.error('Payment processing failed:', error);
            this.showToast('Failed to process payment', 'error');
        }
    },

    // Complete the sale
    completeSale() {
        // TODO: Save sale to database
        const saleData = {
            items: this.cart,
            total: this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
            timestamp: new Date()
        };

        console.log('Sale completed:', saleData);

        // Clear cart and close modal
        this.clearCart();
        bootstrap.Modal.getInstance(document.querySelector('#paymentModal')).hide();
        this.showToast('Sale completed successfully!');
    },

    // Clear cart
    clearCart() {
        this.cart = [];
        this.updateCartDisplay();
        document.querySelector('#cashReceived').value = '';
        document.querySelector('#changeAmount').value = '';
    },

    // Setup event listeners
    setupEventListeners() {
        // Product search
        document.querySelector('#searchProducts').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.product-item').forEach(item => {
                const name = item.querySelector('.card-title').textContent.toLowerCase();
                item.style.display = name.includes(searchTerm) ? '' : 'none';
            });
        });

        // Add to cart
        document.querySelector('#productsGrid').addEventListener('click', (e) => {
            const productCard = e.target.closest('.product-item');
            if (productCard) {
                const productId = parseInt(productCard.dataset.id);
                this.addToCart(productId);
            }
        });

        // Cart quantity controls
        document.querySelector('#cartItems').addEventListener('click', (e) => {
            const button = e.target.closest('button');
            if (!button) return;

            const productId = parseInt(button.dataset.id);
            const cartItem = this.cart.find(item => item.id === productId);
            const product = this.sampleProducts.find(p => p.id === productId);

            if (button.classList.contains('increase-qty')) {
                if (cartItem.quantity < product.stock) {
                    cartItem.quantity++;
                    this.updateCartDisplay();
                } else {
                    this.showToast('Not enough stock!', 'danger');
                }
            } else if (button.classList.contains('decrease-qty')) {
                if (cartItem.quantity > 1) {
                    cartItem.quantity--;
                    this.updateCartDisplay();
                }
            } else if (button.classList.contains('remove-item')) {
                this.cart = this.cart.filter(item => item.id !== productId);
                this.updateCartDisplay();
            }
        });

        // Clear cart
        document.querySelector('#clearCartBtn').addEventListener('click', () => {
            if (confirm('Are you sure you want to clear the cart?')) {
                this.clearCart();
            }
        });

        // Checkout process
        document.querySelector('#checkoutBtn').addEventListener('click', () => {
            new bootstrap.Modal(document.querySelector('#paymentModal')).show();
        });

        // Process payment
        document.querySelector('#cashReceived').addEventListener('input', () => {
            this.processPayment();
        });

        // Complete sale
        document.querySelector('#completePaymentBtn').addEventListener('click', () => {
            this.completeSale();
        });

        // Discount code input
        const discountInput = document.querySelector('#discountCode');
        if (discountInput) {
            discountInput.addEventListener('change', async (e) => {
                const code = e.target.value.trim();
                if (code) {
                    try {
                        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                        const discountResult = await Api.validateDiscount(code, subtotal, this.cart);
                        this.discountCode = code;
                        this.discountAmount = discountResult.amount;
                        this.showToast(`Discount applied: ₱${this.discountAmount.toFixed(2)}`);
                    } catch (error) {
                        this.discountCode = null;
                        this.discountAmount = 0;
                        this.showToast('Invalid discount code', 'error');
                    }
                    this.updateCartSummary();
                }
            });
        }
    },

    // Show toast notification
    showToast(message, type = 'success') {
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }

        const toastHtml = `
            <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => POSManager.init());
