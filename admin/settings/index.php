<?php
error_reporting(E_ALL); // Report all types of errors
ini_set('display_errors', 1);

session_start(); 
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../index.php');
}

$page_title = 'System Settings';
include_once '../../includes/header.php';

// Get current settings
$query = "SELECT * FROM settings";
$result = mysqli_query($conn, $query);
$settings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    $success = [];

    // Store Logo
    if (isset($_FILES['store_logo']) && $_FILES['store_logo']['size'] > 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['store_logo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Logo must be JPG, JPEG or PNG";
        } else {
            $new_filename = 'logo_' . time() . '.' . $ext;
            $destination = '../../uploads/' . $new_filename;

            if (move_uploaded_file($_FILES['store_logo']['tmp_name'], $destination)) {
                // Delete old logo if exists
                if (!empty($settings['store_logo'])) {
                    @unlink('../../uploads/' . $settings['store_logo']);
                }
                updateSetting('store_logo', $new_filename);
                $success[] = "Store logo updated successfully";
            } else {
                $errors[] = "Error uploading logo";
            }
        }
    }

    // Update other settings
    $text_settings = [
        'store_name',
        'store_address',
        'store_phone',
        'store_email',
        'receipt_header',
        'receipt_footer',
        'tax_rate',
        'currency_symbol',
        'low_stock_threshold'
    ];

    foreach ($text_settings as $key) {
        if (isset($_POST[$key])) {
            $value = clean($_POST[$key]);
            updateSetting($key, $value);
            $success[] = ucwords(str_replace('_', ' ', $key)) . " updated successfully";
        }
    }

    // Update checkbox settings
    $checkbox_settings = [
        'enable_tax',
        'show_stock_warning',
        'require_customer_details',
        'enable_discount'
    ];

    foreach ($checkbox_settings as $key) {
        $value = isset($_POST[$key]) ? '1' : '0';
        updateSetting($key, $value);
    }

    // Refresh settings after update
    $result = mysqli_query($conn, "SELECT * FROM settings");
    $settings = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">System Settings</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <ul class="mb-0">
                                <?php foreach ($success as $message): ?>
                                    <li><?php echo $message; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <!-- Store Information -->
                        <h6 class="mb-3">Store Information</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Store Name</label>
                                    <input type="text" name="store_name" class="form-control" 
                                           value="<?php echo $settings['store_name'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Store Logo</label>
                                    <div class="custom-file">
                                        <input type="file" name="store_logo" class="custom-file-input" 
                                               id="storeLogo" accept="image/*">
                                        <label class="custom-file-label" for="storeLogo">
                                            Choose file
                                        </label>
                                    </div>
                                    <?php if (!empty($settings['store_logo'])): ?>
                                        <img src="../../uploads/<?php echo $settings['store_logo']; ?>" 
                                             class="mt-2" height="50" alt="Store Logo">
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Store Address</label>
                                    <textarea name="store_address" class="form-control" 
                                              rows="2"><?php echo $settings['store_address'] ?? ''; ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" name="store_phone" class="form-control" 
                                           value="<?php echo $settings['store_phone'] ?? ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="store_email" class="form-control" 
                                           value="<?php echo $settings['store_email'] ?? ''; ?>">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Receipt Settings -->
                        <h6 class="mb-3">Receipt Settings</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Receipt Header</label>
                                    <textarea name="receipt_header" class="form-control" 
                                              rows="3"><?php echo $settings['receipt_header'] ?? ''; ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Receipt Footer</label>
                                    <textarea name="receipt_footer" class="form-control" 
                                              rows="3"><?php echo $settings['receipt_footer'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Financial Settings -->
                        <h6 class="mb-3">Financial Settings</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Currency Symbol</label>
                                    <input type="text" name="currency_symbol" class="form-control" 
                                           value="<?php echo $settings['currency_symbol'] ?? '$'; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Tax Rate (%)</label>
                                    <input type="number" name="tax_rate" class="form-control" step="0.01" 
                                           value="<?php echo $settings['tax_rate'] ?? '0'; ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Low Stock Threshold</label>
                                    <input type="number" name="low_stock_threshold" class="form-control" 
                                           value="<?php echo $settings['low_stock_threshold'] ?? '10'; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- System Features -->
                        <hr>
                        <h6 class="mb-3">System Features</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="enable_tax" 
                                           class="custom-control-input" id="enableTax" 
                                           <?php echo ($settings['enable_tax'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enableTax">
                                        Enable Tax
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="show_stock_warning" 
                                           class="custom-control-input" id="showStockWarning" 
                                           <?php echo ($settings['show_stock_warning'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="showStockWarning">
                                        Show Stock Warnings
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="require_customer_details" 
                                           class="custom-control-input" id="requireCustomer" 
                                           <?php echo ($settings['require_customer_details'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="requireCustomer">
                                        Require Customer Details
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="enable_discount" 
                                           class="custom-control-input" id="enableDiscount" 
                                           <?php echo ($settings['enable_discount'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="enableDiscount">
                                        Enable Discounts
                                    </label>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update file input label with selected filename
document.querySelector('.custom-file-input').addEventListener('change', function(e) {
    var fileName = e.target.files[0].name;
    var label = e.target.nextElementSibling;
    label.innerHTML = fileName;
});
</script>

<?php include_once '../../includes/footer.php'; ?>
