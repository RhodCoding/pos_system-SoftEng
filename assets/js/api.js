// API Helper Class
class Api {
    static async request(endpoint, options = {}) {
        try {
            const response = await fetch(endpoint, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                credentials: 'include' // Include cookies for session
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'An error occurred');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // Authentication
    static async login(username, password) {
        return await this.request('/auth.php', {
            method: 'POST',
            body: JSON.stringify({ username, password })
        });
    }

    // Products
    static async getProducts() {
        return await this.request('/api/products.php');
    }

    static async getProduct(id) {
        return await this.request(`/api/products.php?id=${id}`);
    }

    static async getProductsByCategory(categoryId) {
        return await this.request(`/api/products.php?action=category&id=${categoryId}`);
    }

    static async getLowStockProducts(threshold = 10) {
        return await this.request(`/api/products.php?action=low_stock&threshold=${threshold}`);
    }

    static async createProduct(productData) {
        return await this.request('/api/products.php', {
            method: 'POST',
            body: JSON.stringify(productData)
        });
    }

    static async updateProduct(id, productData) {
        return await this.request(`/api/products.php?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify(productData)
        });
    }

    static async deleteProduct(id) {
        return await this.request(`/api/products.php?id=${id}`, {
            method: 'DELETE'
        });
    }

    // Orders
    static async createOrder(orderData) {
        return await this.request('/api/orders.php', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });
    }

    static async getOrders(params = {}) {
        const queryString = new URLSearchParams(params).toString();
        return await this.request(`/api/orders.php${queryString ? '?' + queryString : ''}`);
    }

    static async getOrder(id) {
        return await this.request(`/api/orders.php?id=${id}`);
    }

    static async getDailySales() {
        return await this.request('/api/orders.php?action=daily_sales');
    }

    static async getMonthlySales() {
        return await this.request('/api/orders.php?action=monthly_sales');
    }

    static async updateOrderStatus(orderId, status) {
        return await this.request('/api/orders.php?action=update_status', {
            method: 'POST',
            body: JSON.stringify({ order_id: orderId, status })
        });
    }

    // Categories
    static async getCategories() {
        return await this.request('/api/categories.php');
    }

    static async getCategory(id) {
        return await this.request(`/api/categories.php?id=${id}`);
    }

    static async getCategoriesWithProducts() {
        return await this.request('/api/categories.php?action=with_products');
    }

    static async createCategory(categoryData) {
        return await this.request('/api/categories.php', {
            method: 'POST',
            body: JSON.stringify(categoryData)
        });
    }

    static async deleteCategory(id) {
        return await this.request(`/api/categories.php?id=${id}`, {
            method: 'DELETE'
        });
    }

    // Reports
    static async getSalesReport(startDate, endDate) {
        return await this.request(`/api/reports.php?action=sales&start=${startDate}&end=${endDate}`);
    }

    static async getInventoryReport() {
        return await this.request('/api/reports.php?action=inventory');
    }

    static async getTopSellingProducts(limit = 10) {
        return await this.request(`/api/reports.php?action=top_selling&limit=${limit}`);
    }

    // Settings
    static async getSettings() {
        return await this.request('/api/settings.php?action=all');
    }

    static async getSetting(key) {
        return await this.request(`/api/settings.php?key=${key}`);
    }

    static async updateSettings(settings) {
        return await this.request('/api/settings.php', {
            method: 'POST',
            body: JSON.stringify({ settings })
        });
    }

    // Discounts
    static async getDiscounts() {
        return await this.request('/api/discounts.php');
    }

    static async getDiscount(id) {
        return await this.request(`/api/discounts.php?id=${id}`);
    }

    static async validateDiscount(code, subtotal, items) {
        return await this.request(`/api/discounts.php?action=validate&code=${code}&subtotal=${subtotal}&items=${JSON.stringify(items)}`);
    }

    static async createDiscount(discountData) {
        return await this.request('/api/discounts.php', {
            method: 'POST',
            body: JSON.stringify(discountData)
        });
    }

    static async updateDiscount(id, discountData) {
        return await this.request('/api/discounts.php', {
            method: 'PUT',
            body: JSON.stringify({ id, ...discountData })
        });
    }

    static async deleteDiscount(id) {
        return await this.request(`/api/discounts.php?id=${id}`, {
            method: 'DELETE'
        });
    }

    // Audit Trail
    static async getAuditHistory(filters = {}) {
        const queryString = new URLSearchParams(filters).toString();
        return await this.request(`/api/audit.php?action=history${queryString ? '&' + queryString : ''}`);
    }

    static async getEntityHistory(entityType, entityId) {
        return await this.request(`/api/audit.php?action=entity&entity_type=${entityType}&entity_id=${entityId}`);
    }

    // Backup & Export
    static async createBackup() {
        window.location.href = '/api/backup.php?action=create';
    }

    static async exportData(type, filters = {}) {
        const queryString = new URLSearchParams(filters).toString();
        window.location.href = `/api/backup.php?action=export&type=${type}${queryString ? '&' + queryString : ''}`;
    }
}

// Example usage:
/*
// Login
try {
    await Api.login('admin', 'admin123');
    console.log('Logged in successfully');
} catch (error) {
    console.error('Login failed:', error);
}

// Get products
try {
    const { products } = await Api.getProducts();
    console.log('Products:', products);
} catch (error) {
    console.error('Failed to fetch products:', error);
}

// Create order
try {
    const orderData = {
        items: [
            { product_id: 1, quantity: 2 },
            { product_id: 3, quantity: 1 }
        ],
        payment_method: 'cash'
    };
    const { order } = await Api.createOrder(orderData);
    console.log('Order created:', order);
} catch (error) {
    console.error('Failed to create order:', error);
}
*/
