// Employee Management
document.addEventListener('DOMContentLoaded', function() {
    const addForm = document.getElementById('addEmployeeForm');
    const editForm = document.getElementById('editEmployeeForm');
    const addModal = document.getElementById('addEmployeeModal');
    
    // Reset form when modal is opened
    if (addModal) {
        addModal.addEventListener('show.bs.modal', function () {
            addForm.reset();
        });
    }
    
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
                    showToast('Employee added successfully', 'success');
                    addForm.reset();
                    const modal = bootstrap.Modal.getInstance(addModal);
                    if (modal) {
                        modal.hide();
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    if (result.errors && result.errors.username === 'Username already exists') {
                        showToast('Username already exists', 'error');
                    } else {
                        showToast(result.message || 'Failed to add employee', 'error');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    }

    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(editForm);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
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
                    showToast('Employee updated successfully', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editEmployeeModal'));
                    if (modal) {
                        modal.hide();
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(result.message || 'Failed to update employee', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            }
        });
    }
});

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

            const editModal = document.getElementById('editEmployeeModal');
            const modal = bootstrap.Modal.getInstance(editModal) || new bootstrap.Modal(editModal);
            modal.show();
        } else {
            showToast('Failed to load employee data', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to load employee data', 'error');
    }
};

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
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showToast(result.message || 'Failed to delete employee', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Failed to delete employee', 'error');
    }
};

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
