// Discounts Management
const DiscountsManager = {
    discountModal: null,
    discountForm: null,
    products: [],

    init() {
        this.setupEventListeners();
        this.loadDiscounts();
        this.loadProducts();
        this.discountModal = new bootstrap.Modal(document.getElementById('discountModal'));
    },

    async loadDiscounts() {
        try {
            const response = await Api.getDiscounts();
            this.renderDiscounts(response.discounts);
        } catch (error) {
            this.showToast('Failed to load discounts', 'error');
        }
    },

    async loadProducts() {
        try {
            const response = await Api.getProducts();
            this.products = response.products;
            this.renderProductList();
        } catch (error) {
            console.error('Failed to load products:', error);
        }
    },

    renderDiscounts(discounts) {
        const tbody = document.getElementById('discountsTableBody');
        tbody.innerHTML = '';

        discounts.forEach(discount => {
            const row = `
                <tr>
                    <td>${discount.code}</td>
                    <td>${this.formatDiscountType(discount.type)}</td>
                    <td>${this.formatDiscountValue(discount)}</td>
                    <td>${discount.min_purchase > 0 ? '₱' + discount.min_purchase.toFixed(2) : '-'}</td>
                    <td>${discount.used_count}/${discount.usage_limit > 0 ? discount.usage_limit : '∞'}</td>
                    <td>${discount.end_date ? new Date(discount.end_date).toLocaleDateString() : 'No expiry'}</td>
                    <td>
                        <span class="badge ${discount.status === 'active' ? 'bg-success' : 'bg-danger'}">
                            ${discount.status}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary me-1" onclick="DiscountsManager.editDiscount(${discount.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="DiscountsManager.deleteDiscount(${discount.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', row);
        });
    },

    renderProductList() {
        const container = document.getElementById('productList');
        if (!container) return;

        container.innerHTML = this.products.map(product => `
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="${product.id}" id="product${product.id}">
                <label class="form-check-label" for="product${product.id}">
                    ${product.name} (₱${product.price.toFixed(2)})
                </label>
            </div>
        `).join('');
    },

    formatDiscountType(type) {
        const types = {
            percentage: 'Percentage',
            fixed: 'Fixed Amount',
            product: 'Product Specific'
        };
        return types[type] || type;
    },

    formatDiscountValue(discount) {
        if (discount.type === 'percentage') {
            return `${discount.value}%`;
        }
        return `₱${discount.value.toFixed(2)}`;
    },

    showAddModal() {
        document.getElementById('modalTitle').textContent = 'Add New Discount';
        document.getElementById('discountForm').reset();
        document.getElementById('discountId').value = '';
        this.discountModal.show();
    },

    async editDiscount(id) {
        try {
            const discount = await Api.getDiscount(id);
            document.getElementById('modalTitle').textContent = 'Edit Discount';
            document.getElementById('discountId').value = id;
            document.getElementById('code').value = discount.code;
            document.getElementById('type').value = discount.type;
            document.getElementById('value').value = discount.value;
            document.getElementById('minPurchase').value = discount.min_purchase;
            document.getElementById('maxDiscount').value = discount.max_discount;
            document.getElementById('usageLimit').value = discount.usage_limit;
            document.getElementById('status').value = discount.status;
            
            if (discount.start_date) {
                document.getElementById('startDate').value = discount.start_date.slice(0, 16);
            }
            if (discount.end_date) {
                document.getElementById('endDate').value = discount.end_date.slice(0, 16);
            }

            // Handle product selection if it's a product-specific discount
            if (discount.type === 'product') {
                document.getElementById('productSelection').style.display = 'block';
                const productIds = discount.product_ids ? discount.product_ids.split(',') : [];
                productIds.forEach(id => {
                    const checkbox = document.getElementById(`product${id}`);
                    if (checkbox) checkbox.checked = true;
                });
            }

            this.discountModal.show();
        } catch (error) {
            this.showToast('Failed to load discount details', 'error');
        }
    },

    async saveDiscount() {
        const formData = {
            code: document.getElementById('code').value,
            type: document.getElementById('type').value,
            value: parseFloat(document.getElementById('value').value),
            min_purchase: parseFloat(document.getElementById('minPurchase').value) || 0,
            max_discount: parseFloat(document.getElementById('maxDiscount').value) || 0,
            usage_limit: parseInt(document.getElementById('usageLimit').value) || 0,
            start_date: document.getElementById('startDate').value || null,
            end_date: document.getElementById('endDate').value || null,
            status: document.getElementById('status').value
        };

        // Add product IDs if it's a product-specific discount
        if (formData.type === 'product') {
            const selectedProducts = Array.from(document.querySelectorAll('#productList input:checked'))
                .map(checkbox => checkbox.value);
            formData.product_ids = selectedProducts;
        }

        const discountId = document.getElementById('discountId').value;

        try {
            if (discountId) {
                await Api.updateDiscount(discountId, formData);
                this.showToast('Discount updated successfully');
            } else {
                await Api.createDiscount(formData);
                this.showToast('Discount created successfully');
            }
            this.discountModal.hide();
            this.loadDiscounts();
        } catch (error) {
            this.showToast('Failed to save discount', 'error');
        }
    },

    async deleteDiscount(id) {
        if (!confirm('Are you sure you want to delete this discount?')) {
            return;
        }

        try {
            await Api.deleteDiscount(id);
            this.showToast('Discount deleted successfully');
            this.loadDiscounts();
        } catch (error) {
            this.showToast('Failed to delete discount', 'error');
        }
    },

    setupEventListeners() {
        // Add discount button
        const addButton = document.querySelector('[data-bs-target="#discountModal"]');
        if (addButton) {
            addButton.addEventListener('click', () => this.showAddModal());
        }

        // Save discount button
        const saveButton = document.getElementById('saveDiscount');
        if (saveButton) {
            saveButton.addEventListener('click', () => this.saveDiscount());
        }

        // Discount type change
        const typeSelect = document.getElementById('type');
        if (typeSelect) {
            typeSelect.addEventListener('change', (e) => {
                const productSelection = document.getElementById('productSelection');
                if (productSelection) {
                    productSelection.style.display = e.target.value === 'product' ? 'block' : 'none';
                }
            });
        }

        // Filters
        const statusFilter = document.getElementById('statusFilter');
        const typeFilter = document.getElementById('typeFilter');
        
        if (statusFilter) {
            statusFilter.addEventListener('change', () => this.loadDiscounts());
        }
        if (typeFilter) {
            typeFilter.addEventListener('change', () => this.loadDiscounts());
        }
    },

    showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastBody = toast.querySelector('.toast-body');
        
        toast.classList.remove('bg-success', 'bg-danger');
        toast.classList.add(type === 'success' ? 'bg-success' : 'bg-danger');
        toast.classList.add('text-white');
        
        toastBody.textContent = message;
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => DiscountsManager.init());
