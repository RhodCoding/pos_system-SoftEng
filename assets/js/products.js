// Product Management System
class ProductManager {
    constructor() {
        this.products = [];
        this.init();
    }

    init() {
        this.loadProducts();
        this.loadCategories();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Search and filters
        document.getElementById('searchProduct').addEventListener('input', () => this.filterProducts());
        document.getElementById('categoryFilter').addEventListener('change', () => this.filterProducts());
        
        // Product form
        document.getElementById('productForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveProduct();
        });

        // Save button
        document.getElementById('saveProduct').addEventListener('click', () => {
            document.getElementById('productForm').dispatchEvent(new Event('submit'));
        });

        // Image preview
        document.getElementById('image').addEventListener('change', (e) => this.handleImagePreview(e));

        // Modal events
        const productModal = document.getElementById('productModal');
        productModal.addEventListener('hidden.bs.modal', () => this.resetForm());
        productModal.addEventListener('show.bs.modal', (e) => {
            if (e.relatedTarget?.dataset.action === 'edit') {
                this.loadProductForEdit(e.relatedTarget.dataset.id);
            }
        });
    }

    async loadCategories() {
        try {
            const response = await fetch('../api/categories.php');
            const data = await response.json();
            
            if (data.success) {
                const categories = data.categories;
                const categorySelect = document.getElementById('category');
                const categoryFilter = document.getElementById('categoryFilter');
                
                categorySelect.innerHTML = '<option value="">Select Category</option>';
                categories.forEach(category => {
                    categorySelect.innerHTML += `<option value="${category.id}">${category.name}</option>`;
                    categoryFilter.innerHTML += `<option value="${category.id}">${category.name}</option>`;
                });
            }
        } catch (error) {
            this.showToast('Failed to load categories', 'danger');
        }
    }

    async loadProducts() {
        try {
            const response = await fetch('../api/products.php');
            const data = await response.json();
            
            if (data.success) {
                this.products = data.products;
                this.displayProducts();
            } else {
                this.showToast('Failed to load products', 'danger');
            }
        } catch (error) {
            this.showToast('Failed to load products', 'danger');
        }
    }

    filterProducts() {
        const searchTerm = document.getElementById('searchProduct').value.toLowerCase();
        const categoryId = document.getElementById('categoryFilter').value;
        
        const filtered = this.products.filter(product => {
            const matchesSearch = product.name.toLowerCase().includes(searchTerm);
            const matchesCategory = !categoryId || product.category_id === categoryId;
            return matchesSearch && matchesCategory;
        });
        
        this.displayProducts(filtered);
    }

    displayProducts(products = this.products) {
        const tbody = document.getElementById('productList');
        tbody.innerHTML = products.map(product => `
            <tr>
                <td>${product.id}</td>
                <td>
                    <img src="${product.image || '../assets/img/no-image.jpg'}" 
                         class="rounded" 
                         alt="${product.name}"
                         style="width: 50px; height: 50px; object-fit: cover;">
                </td>
                <td>${product.name}</td>
                <td>${product.category}</td>
                <td>₱${parseFloat(product.price).toFixed(2)}</td>
                <td>
                    <span class="badge ${product.stock <= product.alert_threshold ? 'bg-danger' : 'bg-success'}">
                        ${product.stock}
                    </span>
                </td>
                <td>
                    <span class="badge ${product.status === 'active' ? 'bg-success' : 'bg-secondary'}">
                        ${product.status}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary me-1" 
                            data-bs-toggle="modal" 
                            data-bs-target="#productModal" 
                            data-action="edit" 
                            data-id="${product.id}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" 
                            onclick="productManager.deleteProduct(${product.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    handleImagePreview(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            const preview = document.getElementById('imagePreview');
            const previewImg = preview.querySelector('img');
            
            reader.onload = (e) => {
                preview.classList.remove('d-none');
                previewImg.src = e.target.result;
            };
            
            reader.readAsDataURL(file);
        }
    }

    async loadProductForEdit(id) {
        try {
            const response = await fetch(`../api/products.php?id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                const product = data.product;
                const form = document.getElementById('productForm');
                
                form.querySelector('#productId').value = product.id;
                form.querySelector('#name').value = product.name;
                form.querySelector('#category').value = product.category_id;
                form.querySelector('#price').value = product.price;
                form.querySelector('#stock').value = product.stock;
                form.querySelector('#alert_threshold').value = product.alert_threshold;
                form.querySelector('#status').value = product.status;
                
                document.getElementById('modalTitle').textContent = 'Edit Product';
                
                if (product.image) {
                    const preview = document.getElementById('imagePreview');
                    preview.classList.remove('d-none');
                    preview.querySelector('img').src = product.image;
                }
            }
        } catch (error) {
            this.showToast('Failed to load product details', 'danger');
        }
    }

    async saveProduct() {
        const form = document.getElementById('productForm');
        const formData = new FormData(form);
        const productId = formData.get('id');
        
        try {
            const response = await fetch('../api/products.php' + (productId ? `?id=${productId}` : ''), {
                method: productId ? 'PUT' : 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast(productId ? 'Product updated successfully' : 'Product added successfully', 'success');
                bootstrap.Modal.getInstance(document.getElementById('productModal')).hide();
                this.loadProducts();
            } else {
                this.showToast(data.message || 'Failed to save product', 'danger');
            }
        } catch (error) {
            this.showToast('Failed to save product', 'danger');
        }
    }

    async deleteProduct(id) {
        if (!confirm('Are you sure you want to delete this product?')) return;
        
        try {
            const response = await fetch(`../api/products.php?id=${id}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Product deleted successfully', 'success');
                this.loadProducts();
            } else {
                this.showToast(data.message || 'Failed to delete product', 'danger');
            }
        } catch (error) {
            this.showToast('Failed to delete product', 'danger');
        }
    }

    resetForm() {
        const form = document.getElementById('productForm');
        form.reset();
        form.querySelector('#productId').value = '';
        document.getElementById('modalTitle').textContent = 'Add New Product';
        document.getElementById('imagePreview').classList.add('d-none');
    }

    showToast(message, type = 'success') {
        const toastContainer = document.querySelector('.toast-container');
        const toastElement = document.createElement('div');
        toastElement.className = `toast align-items-center text-white bg-${type} border-0`;
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');
        
        toastElement.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toastElement);
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Form submission for adding product
    const addForm = document.getElementById('addProductForm');
    if (addForm) {
        addForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(addForm);
            
            try {
                const response = await fetch('../api/products.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    showToast('Product added successfully', 'success');
                    addForm.reset();
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                    if (modal) {
                        modal.hide();
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message || 'Failed to add product', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    }

    // Delete product handler
    document.addEventListener('click', async function(e) {
        if (e.target.classList.contains('delete-product')) {
            const id = e.target.dataset.id;
            if (confirm('Are you sure you want to delete this product?')) {
                try {
                    const response = await fetch(`../api/products.php?id=${id}`, {
                        method: 'DELETE'
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        showToast('Product deleted successfully', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(result.message || 'Failed to delete product', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('Failed to delete product', 'error');
                }
            }
        }
    });

    // Image preview
    const imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('img');
                    preview.src = e.target.result;
                    preview.style.maxWidth = '200px';
                    preview.style.marginTop = '10px';
                    
                    const previewContainer = imageInput.parentElement;
                    const existingPreview = previewContainer.querySelector('img');
                    if (existingPreview) {
                        previewContainer.removeChild(existingPreview);
                    }
                    previewContainer.appendChild(preview);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Search functionality
    const searchInput = document.getElementById('searchProducts');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const name = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const category = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                
                if (name.includes(searchTerm) || category.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Category filter
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function(e) {
            const selectedCategory = e.target.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const category = row.querySelector('td:nth-child(4)').textContent;
                if (!selectedCategory || category === selectedCategory) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});

// Function to show toast notifications
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.body.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', function () {
        document.body.removeChild(toast);
    });
}

// Initialize the Product Manager
const productManager = new ProductManager();
