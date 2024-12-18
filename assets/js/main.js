// Simple Bakery POS System

const BakeryPOS = {
    elements: {
        categoryPills: document.querySelector('#categoryPills'),
        cartItems: document.querySelector('[data-cart-items]'),
        totalAmount: document.querySelector('[data-total-amount]'),
        subtotalAmount: document.querySelector('[data-subtotal-amount]'),
        cashReceived: document.querySelector('[data-cash-received]'),
        changeAmount: document.querySelector('[data-change-amount]'),
        completeBtn: document.querySelector('[data-complete-sale]'),
        clearCartBtn: document.querySelector('[data-clear-cart]'),
        productSearch: document.querySelector('#productSearch'),
        productCards: document.querySelectorAll('.product-card')
    },
    
    // Cart Management
    cart: {
        items: [],
        
        addItem(product, quantity = 1) {
            const existingItem = this.items.find(item => item.id === product.id);
            
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                this.items.push({ ...product, quantity: 1 });
            }
            
            BakeryPOS.updateCart();
            this.showAddedToast(product.name);
        },
        
        removeItem(productId) {
            const itemIndex = this.items.findIndex(item => item.id === productId);
            if (itemIndex !== -1) {
                this.items.splice(itemIndex, 1);
                BakeryPOS.updateCart();
            }
        },
        
        updateQuantity(productId, newQuantity) {
            const item = this.items.find(item => item.id === productId);
            if (item) {
                if (newQuantity <= 0) {
                    this.removeItem(productId);
                } else {
                    item.quantity = newQuantity;
                    BakeryPOS.updateCart();
                }
            }
        },
        
        getTotal() {
            return this.items.reduce((total, item) => total + (item.price * item.quantity), 0);
        },
        
        clear() {
            if (this.items.length === 0) return;
            
            if (confirm('Are you sure you want to clear the cart?')) {
                this.items = [];
                BakeryPOS.updateCart();
                // Reset cash and change displays
                if (BakeryPOS.elements.cashReceived) BakeryPOS.elements.cashReceived.value = '';
                if (BakeryPOS.elements.changeAmount) BakeryPOS.elements.changeAmount.textContent = '0.00';
                if (BakeryPOS.elements.completeBtn) BakeryPOS.elements.completeBtn.disabled = true;
            }
        },

        showAddedToast(productName) {
            // Create toast container if it doesn't exist
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }

            // Create toast element
            const toastHtml = `
                <div class="toast align-items-center text-white bg-success border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-check-circle me-2"></i>
                            Added ${productName} to cart
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);

            // Initialize and show toast
            const toastElement = toastContainer.lastElementChild;
            const toast = new bootstrap.Toast(toastElement, { delay: 2000 });
            toast.show();

            // Remove toast after it's hidden
            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        }
    },

    // Category Management
    categories: {
        current: 'all',

        show(category) {
            this.current = category;
            document.querySelectorAll('[data-category]').forEach(product => {
                if (category === 'all' || product.dataset.category === category) {
                    product.style.display = '';
                } else {
                    product.style.display = 'none';
                }
            });

            // Update active pill
            document.querySelectorAll('#categoryPills .nav-link').forEach(pill => {
                if (pill.dataset.category === category) {
                    pill.classList.add('active');
                } else {
                    pill.classList.remove('active');
                }
            });
        }
    },
    
    // Search functionality
    search: {
        active: false,
        currentCategory: 'all',

        filterProducts(query) {
            const searchTerm = query.toLowerCase().trim();
            
            document.querySelectorAll('[data-category]').forEach(categoryDiv => {
                const category = categoryDiv.getAttribute('data-category');
                const shouldShowCategory = this.currentCategory === 'all' || this.currentCategory === category;
                
                categoryDiv.querySelectorAll('.product-card').forEach(card => {
                    const productName = card.querySelector('.card-title').textContent.toLowerCase();
                    const matchesSearch = searchTerm === '' || productName.includes(searchTerm);
                    
                    // Show product if it matches both category and search
                    card.closest('.col').style.display = (shouldShowCategory && matchesSearch) ? '' : 'none';
                });
            });
        },

        handleSearchInput(event) {
            this.filterProducts(event.target.value);
        },

        handleKeyboardShortcuts(event) {
            // Search shortcut: '/'
            if (event.key === '/' && !this.active) {
                event.preventDefault();
                BakeryPOS.elements.productSearch.focus();
                this.active = true;
            }
            // Exit search: 'Escape'
            else if (event.key === 'Escape' && this.active) {
                BakeryPOS.elements.productSearch.blur();
                BakeryPOS.elements.productSearch.value = '';
                this.filterProducts('');
                this.active = false;
            }
        },

        setCategory(category) {
            this.currentCategory = category;
            this.filterProducts(BakeryPOS.elements.productSearch.value);
        }
    },
    
    // Calculate change
    calculateChange(cashReceived) {
        const total = this.cart.getTotal();
        return Math.max(0, cashReceived - total);
    },
    
    // Format currency
    formatCurrency(amount) {
        return amount.toFixed(2);
    },
    
    // Update cart display
    updateCart() {
        if (!this.elements.cartItems) return;
        
        if (this.cart.items.length === 0) {
            this.elements.cartItems.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="bi bi-cart3 fs-1"></i>
                    <p class="mt-2">Cart is empty</p>
                </div>
            `;
        } else {
            this.elements.cartItems.innerHTML = this.cart.items.map(item => `
                <div class="cart-item mb-2 p-2 border rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold">${item.name}</span>
                            <br>
                            <small class="text-muted">₱${this.formatCurrency(item.price)} each</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 120px;">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="BakeryPOS.cart.updateQuantity('${item.id}', ${item.quantity - 1})">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input type="number" class="form-control text-center" 
                                       value="${item.quantity}" min="1"
                                       onchange="BakeryPOS.cart.updateQuantity('${item.id}', parseInt(this.value) || 1)">
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="BakeryPOS.cart.updateQuantity('${item.id}', ${item.quantity + 1})">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>
                            <span class="mx-2 text-nowrap">₱${this.formatCurrency(item.price * item.quantity)}</span>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="BakeryPOS.cart.removeItem('${item.id}')"
                                    title="Remove item">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        const total = this.cart.getTotal();
        
        if (this.elements.subtotalAmount) {
            this.elements.subtotalAmount.textContent = this.formatCurrency(total);
        }
        
        if (this.elements.totalAmount) {
            this.elements.totalAmount.textContent = this.formatCurrency(total);
        }

        // Update cash received if it exists
        if (this.elements.cashReceived && this.elements.cashReceived.value) {
            this.handleCashPayment();
        }
    },
    
    // Handle cash payment
    handleCashPayment() {
        const cashReceived = parseFloat(this.elements.cashReceived.value) || 0;
        const total = this.cart.getTotal();
        
        const change = this.calculateChange(cashReceived);
        this.elements.changeAmount.textContent = this.formatCurrency(change);
        
        // Enable complete sale button if payment is sufficient
        if (this.elements.completeBtn) {
            this.elements.completeBtn.disabled = cashReceived < total || total === 0;
            
            // Add visual feedback
            if (cashReceived < total) {
                this.elements.cashReceived.classList.add('is-invalid');
            } else {
                this.elements.cashReceived.classList.remove('is-invalid');
            }
        }
    },
    
    // Complete sale
    completeSale() {
        const total = this.cart.getTotal();
        const cashReceived = parseFloat(this.elements.cashReceived.value);
        const change = this.calculateChange(cashReceived);

        // Show sale summary
        const summary = `
            Sale Complete!
            
            Total: ₱${this.formatCurrency(total)}
            Cash: ₱${this.formatCurrency(cashReceived)}
            Change: ₱${this.formatCurrency(change)}
            
            Thank you for your purchase!
        `;
        
        alert(summary);
        
        // Get receipt data
        const receiptData = {
            items: this.cart.items,
            total: total,
            cashReceived: cashReceived,
            change: change,
            date: new Date()
        };

        // TODO: Save sale to database
        console.log('Sale completed:', receiptData);
        
        // Clear cart and reset form
        this.cart.clear();
    },
    
    // Initialize
    init() {
        // Product click handlers
        document.querySelectorAll('[data-product]').forEach(product => {
            const button = product.querySelector('button');
            if (button) {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const productData = {
                        id: product.dataset.productId,
                        name: product.dataset.productName,
                        price: parseFloat(product.dataset.productPrice),
                        category: product.closest('[data-category]').dataset.category
                    };
                    this.cart.addItem(productData);
                });
            }
        });

        // Search functionality
        if (this.elements.productSearch) {
            this.elements.productSearch.addEventListener('input', (e) => this.search.handleSearchInput.call(this.search, e));
            document.addEventListener('keydown', (e) => this.search.handleKeyboardShortcuts.call(this.search, e));
        }

        // Make product cards focusable
        this.elements.productCards.forEach(card => {
            card.setAttribute('tabindex', '0');
        });

        // Category pill handlers
        if (this.elements.categoryPills) {
            this.elements.categoryPills.addEventListener('click', (e) => {
                e.preventDefault();
                const pill = e.target.closest('.nav-link');
                if (pill && pill.dataset.category) {
                    // Update active state
                    document.querySelectorAll('#categoryPills .nav-link').forEach(p => p.classList.remove('active'));
                    pill.classList.add('active');
                    
                    // Update category and filter
                    this.search.setCategory(pill.dataset.category);
                }
            });
        }
        
        // Cash received input handler
        if (this.elements.cashReceived) {
            this.elements.cashReceived.addEventListener('input', () => this.handleCashPayment());
        }
        
        // Complete sale button
        if (this.elements.completeBtn) {
            this.elements.completeBtn.addEventListener('click', () => this.completeSale());
        }

        // Clear cart button
        if (this.elements.clearCartBtn) {
            this.elements.clearCartBtn.addEventListener('click', () => this.cart.clear());
        }

        // Initialize with all categories shown
        this.categories.show('all');
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => BakeryPOS.init());
