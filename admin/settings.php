<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Bakery POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block sidebar">
                <?php include 'components/sidebar.php'; ?>
            </nav>

            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3">
                    <h2>Settings</h2>
                    <div>
                        <button class="btn btn-success" onclick="saveSettings()">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Store Information</h5>
                            </div>
                            <div class="card-body">
                                <form id="storeSettingsForm">
                                    <div class="mb-3">
                                        <label class="form-label">Store Name</label>
                                        <input type="text" class="form-control" name="store_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" name="address" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="tel" class="form-control" name="contact_number">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" name="email">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Receipt Settings</h5>
                            </div>
                            <div class="card-body">
                                <form id="receiptSettingsForm">
                                    <div class="mb-3">
                                        <label class="form-label">Receipt Header</label>
                                        <textarea class="form-control" name="receipt_header" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Receipt Footer</label>
                                        <textarea class="form-control" name="receipt_footer" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tax Rate (%)</label>
                                        <input type="number" class="form-control" name="tax_rate" step="0.01" min="0" max="100">
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">System Settings</h5>
                            </div>
                            <div class="card-body">
                                <form id="systemSettingsForm">
                                    <div class="mb-3">
                                        <label class="form-label">Low Stock Alert Threshold</label>
                                        <input type="number" class="form-control" name="low_stock_threshold" min="1">
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="enable_discounts">
                                            <label class="form-check-label">Enable Discounts</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="enable_tax">
                                            <label class="form-check-label">Enable Tax Calculation</label>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function saveSettings() {
            const storeSettings = new FormData(document.getElementById('storeSettingsForm'));
            const receiptSettings = new FormData(document.getElementById('receiptSettingsForm'));
            const systemSettings = new FormData(document.getElementById('systemSettingsForm'));

            // Combine all settings
            const allSettings = {
                store: Object.fromEntries(storeSettings),
                receipt: Object.fromEntries(receiptSettings),
                system: Object.fromEntries(systemSettings)
            };

            // Save settings
            fetch('../api/settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(allSettings)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Settings saved successfully!');
                } else {
                    alert(data.error || 'Failed to save settings');
                }
            });
        }

        // Load settings when page loads
        function loadSettings() {
            fetch('../api/settings.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Populate store settings
                    const storeForm = document.getElementById('storeSettingsForm');
                    Object.keys(data.settings.store).forEach(key => {
                        const input = storeForm.elements[key];
                        if (input) input.value = data.settings.store[key];
                    });

                    // Populate receipt settings
                    const receiptForm = document.getElementById('receiptSettingsForm');
                    Object.keys(data.settings.receipt).forEach(key => {
                        const input = receiptForm.elements[key];
                        if (input) input.value = data.settings.receipt[key];
                    });

                    // Populate system settings
                    const systemForm = document.getElementById('systemSettingsForm');
                    Object.keys(data.settings.system).forEach(key => {
                        const input = systemForm.elements[key];
                        if (input) {
                            if (input.type === 'checkbox') {
                                input.checked = data.settings.system[key];
                            } else {
                                input.value = data.settings.system[key];
                            }
                        }
                    });
                }
            });
        }

        document.addEventListener('DOMContentLoaded', loadSettings);
    </script>
</body>
</html>