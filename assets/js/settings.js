// Settings Page Management
const SettingsManager = {
    elements: {
        businessForm: document.querySelector('#businessForm'),
        receiptForm: document.querySelector('#receiptForm'),
        saveButton: document.querySelector('#saveSettings')
    },

    // Sample settings data structure (for frontend development)
    sampleSettings: {
        business: {
            name: 'My Bakery',
            address: '123 Sample Street, Barangay Sample, City',
            contact: '+63 912 345 6789'
        },
        receipt: {
            header: 'Welcome to My Bakery!',
            footer: 'Thank you, Please come again!'
        }
    },

    // Load settings
    loadSettings() {
        // TODO: Replace with API call when backend is ready
        const settings = this.sampleSettings;
        
        // Load business info
        document.querySelector('#businessName').value = settings.business.name;
        document.querySelector('#businessAddress').value = settings.business.address;
        document.querySelector('#businessContact').value = settings.business.contact;

        // Load receipt settings
        document.querySelector('#receiptHeader').value = settings.receipt.header;
        document.querySelector('#receiptFooter').value = settings.receipt.footer;
    },

    // Save settings
    saveSettings() {
        // Collect all form data
        const settings = {
            business: {
                name: document.querySelector('#businessName').value,
                address: document.querySelector('#businessAddress').value,
                contact: document.querySelector('#businessContact').value
            },
            receipt: {
                header: document.querySelector('#receiptHeader').value,
                footer: document.querySelector('#receiptFooter').value
            }
        };

        // TODO: Replace with API call when backend is ready
        console.log('Settings to save:', settings);
        
        // Show success message
        this.showToast('Settings saved successfully!', 'success');
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
    },

    // Initialize
    init() {
        // Load initial settings
        this.loadSettings();

        // Add save button handler
        if (this.elements.saveButton) {
            this.elements.saveButton.addEventListener('click', () => this.saveSettings());
        }
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => SettingsManager.init());
