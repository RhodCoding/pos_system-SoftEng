// POS System Management for Employees
const POSManager = {
    // Cart data
    cart: [],
    products: [],

    // Initialize POS system
    async init() {
        await this.loadProducts();
        this.setupEventListeners();
        this.updateCartSummary();
    },

    // Load products from API
    async loadProducts() {
        try {
            const response = await Api.getProducts();
            this.products = response.products;
            this.displayProducts(this.products);
        } catch (error) {
            console.error('Failed to load products:', error);
            this.showToast('Failed to load products. Please try again.', 'danger');
        }
    },

    // Display products in the grid
    displayProducts(products) {
        const grid = document.querySelector('#productsGrid');
        grid.innerHTML = '';

        products.forEach(product => {
            const productHtml = `
                <div class="card product-item" data-id="${product.id}">
                    <img src="${product.image_url || '../assets/images/products/default.png'}" 
                         class="product-image card-img-top" 
                         alt="${product.name}"
                         onerror="this.src='../assets/images/products/default.png'">
                    <div class="card-body text-center">
                        <h6 class="card-title">${product.name}</h6>
                        <p class="card-text">₱${parseFloat(product.price).toFixed(2)}</p>
                        <small class="text-muted">Stock: ${product.stock_quantity}</small>
                    </div>
                </div>
            `;
            grid.insertAdjacentHTML('beforeend', productHtml);
        });
    },

    // Add item to cart
    addToCart(productId) {
        const product = this.products.find(p => p.id === productId);
        if (!product) return;

        const cartItem = this.cart.find(item => item.id === productId);
        if (cartItem) {
            if (cartItem.quantity < product.stock_quantity) {
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
                price: parseFloat(product.price),
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

    // Update cart summary
    updateCartSummary() {
        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        document.querySelector('#subtotal').textContent = subtotal.toFixed(2);
        document.querySelector('#total').textContent = subtotal.toFixed(2);

        // Update payment modal total if it's open
        const totalAmountInput = document.querySelector('#totalAmount');
        if (totalAmountInput) {
            totalAmountInput.value = `₱${subtotal.toFixed(2)}`;
        }

        // Enable/disable checkout button
        const checkoutBtn = document.querySelector('#checkoutBtn');
        if (checkoutBtn) {
            checkoutBtn.disabled = this.cart.length === 0;
        }
    },

    // Process payment
    processPayment() {
        const total = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const cash = parseFloat(document.querySelector('#cashReceived').value) || 0;
        const change = cash - total;

        document.querySelector('#totalAmount').value = `₱${total.toFixed(2)}`;
        document.querySelector('#changeAmount').value = change >= 0 ? `₱${change.toFixed(2)}` : '';
        
        const completeBtn = document.querySelector('#completePaymentBtn');
        completeBtn.disabled = change < 0 || cash === 0;

        return { total, cash, change };
    },

    // Complete sale
    async completeSale() {
        const { total, cash, change } = this.processPayment();
        
        if (cash >= total) {
            try {
                // Create order through API
                const orderData = {
                    items: this.cart.map(item => ({
                        product_id: item.id,
                        quantity: item.quantity
                    })),
                    payment_method: 'cash',
                    cash_received: cash,
                    change_amount: change
                };

                const response = await Api.createOrder(orderData);
                
                this.showToast('Sale completed successfully!', 'success');
                this.clearCart();
                await this.loadProducts(); // Reload products to update stock
                
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.querySelector('#paymentModal'));
                if (modal) {
                    modal.hide();
                }

                // Reset payment form
                document.querySelector('#cashReceived').value = '';
                document.querySelector('#changeAmount').value = '';

            } catch (error) {
                console.error('Failed to complete sale:', error);
                this.showToast('Failed to complete sale. Please try again.', 'danger');
            }
        } else {
            this.showToast('Insufficient cash amount!', 'danger');
        }
    },

    // Clear cart
    clearCart() {
        this.cart = [];
        this.updateCartDisplay();
    },

    // Setup event listeners
    setupEventListeners() {
        // Product search
        document.querySelector('#searchProducts').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const filteredProducts = this.products.filter(product => 
                product.name.toLowerCase().includes(searchTerm)
            );
            this.displayProducts(filteredProducts);
        });

        // Add to cart
        document.querySelector('#productsGrid').addEventListener('click', (e) => {
            const productCard = e.target.closest('.product-item');
            if (productCard) {
                const productId = parseInt(productCard.dataset.id);
                this.addToCart(productId);
            }
        });

        // Cart quantity controls and remove item
        document.querySelector('#cartItems').addEventListener('click', (e) => {
            const button = e.target.closest('button');
            if (!button) return;

            const productId = parseInt(button.dataset.id);
            const cartItem = this.cart.find(item => item.id === productId);
            const product = this.products.find(p => p.id === productId);

            if (!cartItem) return;

            if (button.classList.contains('increase-qty')) {
                if (cartItem.quantity < product.stock_quantity) {
                    cartItem.quantity++;
                } else {
                    this.showToast('Not enough stock!', 'danger');
                }
            } else if (button.classList.contains('decrease-qty')) {
                if (cartItem.quantity > 1) {
                    cartItem.quantity--;
                } else {
                    this.cart = this.cart.filter(item => item.id !== productId);
                }
            } else if (button.classList.contains('remove-item')) {
                this.cart = this.cart.filter(item => item.id !== productId);
            }

            this.updateCartDisplay();
        });

        // Clear cart
        document.querySelector('#clearCartBtn').addEventListener('click', () => {
            if (this.cart.length > 0) {
                if (confirm('Are you sure you want to clear the cart?')) {
                    this.clearCart();
                }
            }
        });

        // Process payment
        document.querySelector('#checkoutBtn').addEventListener('click', () => {
            if (this.cart.length === 0) {
                this.showToast('Cart is empty!', 'warning');
                return;
            }
            const modal = new bootstrap.Modal(document.querySelector('#paymentModal'));
            modal.show();
        });

        // Calculate change
        document.querySelector('#cashReceived').addEventListener('input', () => {
            this.processPayment();
        });

        // Complete payment
        document.querySelector('#completePaymentBtn').addEventListener('click', () => {
            this.completeSale();
        });

        // Refresh products periodically (every 5 minutes)
        setInterval(() => this.loadProducts(), 5 * 60 * 1000);
    },

    // Show toast notification
    showToast(message, type = 'success') {
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        document.body.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        
        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => POSManager.init());
