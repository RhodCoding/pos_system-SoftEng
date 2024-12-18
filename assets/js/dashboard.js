// Dashboard functionality
class Dashboard {
    constructor() {
        this.salesChart = null;
        this.initializeCharts();
        this.loadDashboardData();
        this.setupRefreshInterval();
    }

    async loadDashboardData() {
        try {
            // Load sales data
            const salesData = await Api.getDailySales();
            document.querySelector('#todaySales').textContent = 
                `₱${parseFloat(salesData.sales.total_sales).toFixed(2)}`;

            // Load product count
            const { products } = await Api.getProducts();
            document.querySelector('#totalProducts').textContent = products.length;

            // Load categories with product count
            const { categories } = await Api.getCategoriesWithProducts();
            document.querySelector('#totalCategories').textContent = categories.length;

            // Load low stock products
            const lowStock = await Api.getLowStockProducts(10);
            document.querySelector('#lowStock').textContent = lowStock.products.length;

            // Update chart
            this.updateSalesChart();
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
            // Show error toast
            showToast('Error loading dashboard data', 'error');
        }
    }

    async updateSalesChart() {
        try {
            // Get last 7 days of sales
            const dates = [];
            const salesData = [];
            
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                const formattedDate = date.toISOString().split('T')[0];
                dates.push(formattedDate);
                
                const { sales } = await Api.getDailySales(formattedDate);
                salesData.push(sales.total_sales || 0);
            }

            if (this.salesChart) {
                this.salesChart.destroy();
            }

            const ctx = document.getElementById('salesChart').getContext('2d');
            this.salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [{
                        label: 'Daily Sales',
                        data: salesData,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => '₱' + value
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: context => '₱' + context.parsed.y
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Failed to update sales chart:', error);
            showToast('Error updating sales chart', 'error');
        }
    }

    setupRefreshInterval() {
        // Refresh dashboard data every 5 minutes
        setInterval(() => this.loadDashboardData(), 5 * 60 * 1000);
    }
}

// Employee Management
document.addEventListener('DOMContentLoaded', function() {
    // Add Employee Form
    const addForm = document.getElementById('addEmployeeForm');
    const editForm = document.getElementById('editEmployeeForm');
    
    if (addForm) {
        addForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(addForm);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            try {
                const response = await fetch('../api/employees.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    addForm.reset();
                    bootstrap.Modal.getInstance(document.getElementById('addEmployeeModal')).hide();
                    showToast('Employee added successfully', 'success');
                    loadEmployees();
                } else {
                    if (result.errors) {
                        Object.keys(result.errors).forEach(field => {
                            const input = document.getElementById('employee' + field.charAt(0).toUpperCase() + field.slice(1));
                            const errorDiv = document.getElementById(field + 'Error');
                            if (input && errorDiv) {
                                input.classList.add('is-invalid');
                                errorDiv.textContent = result.errors[field];
                            }
                        });
                    } else {
                        showToast(result.message || 'Failed to add employee', 'error');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Failed to add employee', 'error');
            }
        });
    }

    if (editForm) {
        // Edit Employee Form
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(editForm);
            const data = {};
            formData.forEach((value, key) => {
                if (key !== 'password' || value !== '') { // Only include password if it's not empty
                    data[key] = value;
                }
            });

            try {
                const response = await fetch('../api/employees.php', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    editForm.reset();
                    bootstrap.Modal.getInstance(document.getElementById('editEmployeeModal')).hide();
                    showToast('Employee updated successfully', 'success');
                    loadEmployees();
                } else {
                    showToast(result.message || 'Failed to update employee', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Failed to update employee', 'error');
            }
        });
    }

    // Clear validation errors when input changes
    document.querySelectorAll('form input').forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('is-invalid');
            const errorDiv = document.getElementById(this.name + 'Error');
            if (errorDiv) errorDiv.textContent = '';
        });
    });

    // Load employees if table exists
    const employeeTable = document.getElementById('employeeTable');
    if (employeeTable) {
        loadEmployees();
    }
});

// Delete Employee
window.deleteEmployee = async function(id) {
    if (!confirm('Are you sure you want to delete this employee?')) {
        return;
    }

    try {
        const response = await fetch(`../api/employees.php?id=${id}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (result.success) {
            showToast('Employee deleted successfully', 'success');
            loadEmployees();
        } else {
            showToast(result.message || 'Failed to delete employee', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to delete employee', 'error');
    }
};

// Edit Employee
window.editEmployee = async function(id) {
    try {
        const response = await fetch(`../api/employees.php?id=${id}`);
        const result = await response.json();

        if (result.success) {
            const employee = result.employee;
            document.getElementById('editEmployeeId').value = employee.id;
            document.getElementById('editEmployeeName').value = employee.name;
            document.getElementById('editEmployeeUsername').value = employee.username;
            document.getElementById('editEmployeeStatus').value = employee.status;
            document.getElementById('editEmployeePassword').value = ''; // Clear password field

            const editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
            editModal.show();
        } else {
            showToast('Failed to load employee data', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to load employee data', 'error');
    }
};

// Function to load employees
async function loadEmployees() {
    try {
        const response = await fetch('../api/employees.php');
        const result = await response.json();

        if (result.success) {
            const tbody = document.querySelector('#employeeTable tbody');
            tbody.innerHTML = result.employees.map(emp => `
                <tr>
                    <td>${emp.name}</td>
                    <td>${emp.username}</td>
                    <td>${emp.status}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editEmployee(${emp.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteEmployee(${emp.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to load employees', 'error');
    }
}

// Function to show toast notifications
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    document.querySelector('.toast-container').appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Toast notification helper
function showToast(message, type = 'info') {
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

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new Dashboard();
});
