<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

include 'db_connection.php';

// Get settings from database
$sql = "SELECT * FROM settings WHERE id = 1";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    // If no settings found, run the installation script
    include 'install_settings.php';
    $result = mysqli_query($conn, $sql);
}

$settings = mysqli_fetch_assoc($result);

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $store_name = mysqli_real_escape_string($conn, $_POST['store_name']);
    $store_email = mysqli_real_escape_string($conn, $_POST['store_email']);
    $store_phone = mysqli_real_escape_string($conn, $_POST['store_phone']);
    $store_address = mysqli_real_escape_string($conn, $_POST['store_address']);
    $currency = mysqli_real_escape_string($conn, $_POST['currency']);
    $tax_rate = floatval($_POST['tax_rate']);
    $shipping_fee = floatval($_POST['shipping_fee']);
    $free_shipping_threshold = floatval($_POST['free_shipping_threshold']);
    $social_facebook = mysqli_real_escape_string($conn, $_POST['social_facebook']);
    $social_twitter = mysqli_real_escape_string($conn, $_POST['social_twitter']);
    $social_instagram = mysqli_real_escape_string($conn, $_POST['social_instagram']);
    $meta_title = mysqli_real_escape_string($conn, $_POST['meta_title']);
    $meta_description = mysqli_real_escape_string($conn, $_POST['meta_description']);
    $meta_keywords = mysqli_real_escape_string($conn, $_POST['meta_keywords']);
    $about_us = mysqli_real_escape_string($conn, $_POST['about_us']);
    $privacy_policy = mysqli_real_escape_string($conn, $_POST['privacy_policy']);
    $terms_conditions = mysqli_real_escape_string($conn, $_POST['terms_conditions']);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;

    // Handle logo upload if provided
    $store_logo = $settings['store_logo'];
    if (isset($_FILES['store_logo']) && $_FILES['store_logo']['size'] > 0) {
        $target_dir = "contecnt/img/";
        $file_extension = strtolower(pathinfo($_FILES["store_logo"]["name"], PATHINFO_EXTENSION));
        $new_filename = "store_logo_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check file type
        if ($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg" && $file_extension != "gif" && $file_extension != "svg") {
            $error_message = "Sorry, only JPG, JPEG, PNG, GIF & SVG files are allowed for logo.";
        } else {
            // Upload file
            if (move_uploaded_file($_FILES["store_logo"]["tmp_name"], $target_file)) {
                // Delete old logo if exists
                if (!empty($store_logo) && file_exists($store_logo)) {
                    unlink($store_logo);
                }
                $store_logo = $target_file;
            } else {
                $error_message = "Sorry, there was an error uploading your logo.";
            }
        }
    }

    // Handle favicon upload if provided
    $store_favicon = $settings['store_favicon'];
    if (isset($_FILES['store_favicon']) && $_FILES['store_favicon']['size'] > 0) {
        $target_dir = "contecnt/img/";
        $file_extension = strtolower(pathinfo($_FILES["store_favicon"]["name"], PATHINFO_EXTENSION));
        $new_filename = "store_favicon_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check file type
        if ($file_extension != "ico" && $file_extension != "png") {
            $error_message = "Sorry, only ICO and PNG files are allowed for favicon.";
        } else {
            // Upload file
            if (move_uploaded_file($_FILES["store_favicon"]["tmp_name"], $target_file)) {
                // Delete old favicon if exists
                if (!empty($store_favicon) && file_exists($store_favicon)) {
                    unlink($store_favicon);
                }
                $store_favicon = $target_file;
            } else {
                $error_message = "Sorry, there was an error uploading your favicon.";
            }
        }
    }

    // Update settings in database if no error
    if (empty($error_message)) {
        $update_sql = "UPDATE settings SET 
            store_name = '$store_name',
            store_email = '$store_email',
            store_phone = '$store_phone',
            store_address = '$store_address',
            currency = '$currency',
            tax_rate = $tax_rate,
            shipping_fee = $shipping_fee,
            free_shipping_threshold = $free_shipping_threshold,
            store_logo = '$store_logo',
            store_favicon = '$store_favicon',
            social_facebook = '$social_facebook',
            social_twitter = '$social_twitter',
            social_instagram = '$social_instagram',
            meta_title = '$meta_title',
            meta_description = '$meta_description',
            meta_keywords = '$meta_keywords',
            about_us = '$about_us',
            privacy_policy = '$privacy_policy',
            terms_conditions = '$terms_conditions',
            maintenance_mode = $maintenance_mode,
            updated_at = NOW()
            WHERE id = 1";

        if (mysqli_query($conn, $update_sql)) {
            $success_message = "Settings updated successfully!";
            // Refresh settings
            $result = mysqli_query($conn, $sql);
            $settings = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Error updating settings: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Kiyara Plants</title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Admin Styles -->
    <link rel="stylesheet" href="contecnt/css/admin_style.css">
    <!-- Admin Sidebar Styles -->
    <link rel="stylesheet" href="contecnt/css/admin_sidebar.css">
    <style>
        /* Tab Navigation */
        .settings-tabs {
            display: flex;
            margin-bottom: 0;
            border-bottom: none;
            background-color: #f8f9fa;
        }
        
        .settings-tab-btn {
            padding: 12px 20px;
            background-color: #f1f1f1;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            color: #555;
            cursor: pointer;
            font-weight: 500;
            margin-right: 5px;
            position: relative;
            transition: all 0.2s;
        }
        
        .settings-tab-btn:hover {
            background-color: #e9ecef;
            color: #28a745;
        }
        
        .settings-tab-btn.active {
            background-color: #fff;
            color: #28a745;
            border-color: #dee2e6;
            border-bottom: 1px solid #fff;
            z-index: 2;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
            padding: 25px;
            border: 1px solid #dee2e6;
            border-radius: 0 0 5px 5px;
            background-color: #fff;
            margin-top: -1px;
            position: relative;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Form Styling */
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
            outline: 0;
        }
        
        .form-label {
            font-weight: 500;
            display: block;
            margin-bottom: 8px;
            color: #333;
        }
        
        .textarea-content {
            min-height: 200px;
            resize: vertical;
        }
        
        .input-group {
            display: flex;
            margin-bottom: 15px;
        }
        
        .input-group-text {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            border-right: none;
            border-top-left-radius: 4px;
            border-bottom-left-radius: 4px;
        }
        
        .input-group .form-control {
            margin-bottom: 0;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        
        .form-text {
            display: block;
            margin-top: 5px;
            font-size: 13px;
            color: #6c757d;
        }
        
        .mt-2 {
            margin-top: 10px;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .form-check-input {
            margin-right: 8px;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -10px;
            margin-left: -10px;
        }
        
        .col-md-6, .col-md-4 {
            padding-right: 10px;
            padding-left: 10px;
            margin-bottom: 15px;
        }
        
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
        }
        
        .col-md-4 {
            flex: 0 0 33.33%;
            max-width: 33.33%;
        }
        
        .btn-success {
            background-color: #28a745;
            border: 1px solid #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 0.25rem;
            background-color: #fff;
        }
        
        .card-body {
            padding: 1.25rem;
        }
        
        @media (max-width: 768px) {
            .col-md-6, .col-md-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .settings-tabs {
                flex-wrap: wrap;
            }
            
            .settings-tab-btn {
                margin-bottom: 5px;
                flex: 1 0 auto;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Include Admin Sidebar -->
    <?php include 'admin_sidebar.php'; ?>
    
    <!-- Content -->
    <div class="content">
        <!-- Navbar -->
        <nav class="navbar">
            <div class="container">
                <span class="navbar-brand">Store Settings</span>
            </div>
        </nav>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" aria-label="Close">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" aria-label="Close">&times;</button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="settings-tabs">
                    <button class="settings-tab-btn active" data-tab="general">General</button>
                    <button class="settings-tab-btn" data-tab="payment">Payment & Shipping</button>
                    <button class="settings-tab-btn" data-tab="seo">SEO</button>
                    <button class="settings-tab-btn" data-tab="social">Social Media</button>
                    <button class="settings-tab-btn" data-tab="pages">Pages</button>
                </div>
                
                <form method="post" enctype="multipart/form-data">
                    <!-- General Settings -->
                    <div id="general" class="tab-content active">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="store_name" class="form-label">Store Name</label>
                                <input type="text" class="form-control" id="store_name" name="store_name" value="<?php echo htmlspecialchars($settings['store_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="store_email" class="form-label">Store Email</label>
                                <input type="email" class="form-control" id="store_email" name="store_email" value="<?php echo htmlspecialchars($settings['store_email']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="store_phone" class="form-label">Store Phone</label>
                                <input type="text" class="form-control" id="store_phone" name="store_phone" value="<?php echo htmlspecialchars($settings['store_phone']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="currency" class="form-label">Currency Symbol</label>
                                <input type="text" class="form-control" id="currency" name="currency" value="<?php echo htmlspecialchars($settings['currency']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="store_address" class="form-label">Store Address</label>
                            <textarea class="form-control" id="store_address" name="store_address" rows="3" required><?php echo htmlspecialchars($settings['store_address']); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="store_logo" class="form-label">Store Logo</label>
                                <input type="file" class="form-control" id="store_logo" name="store_logo">
                                <?php if (!empty($settings['store_logo'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($settings['store_logo']); ?>" alt="Store Logo" style="max-height: 80px;">
                                    </div>
                                <?php endif; ?>
                                <small class="form-text">Recommended size: 200x60px. Allowed formats: JPG, PNG, GIF, SVG</small>
                            </div>
                            <div class="col-md-6">
                                <label for="store_favicon" class="form-label">Store Favicon</label>
                                <input type="file" class="form-control" id="store_favicon" name="store_favicon">
                                <?php if (!empty($settings['store_favicon'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($settings['store_favicon']); ?>" alt="Store Favicon" style="max-height: 32px;">
                                    </div>
                                <?php endif; ?>
                                <small class="form-text">Recommended size: 32x32px. Allowed formats: ICO, PNG</small>
                            </div>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintenance_mode">Enable Maintenance Mode</label>
                            <small class="form-text">When enabled, only admins can access the site.</small>
                        </div>
                    </div>

                    <!-- Payment & Shipping Settings -->
                    <div id="payment" class="tab-content">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="tax_rate" name="tax_rate" value="<?php echo htmlspecialchars($settings['tax_rate']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="shipping_fee" class="form-label">Shipping Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text"><?php echo htmlspecialchars($settings['currency']); ?></span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="shipping_fee" name="shipping_fee" value="<?php echo htmlspecialchars($settings['shipping_fee']); ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="free_shipping_threshold" class="form-label">Free Shipping Threshold</label>
                                <div class="input-group">
                                    <span class="input-group-text"><?php echo htmlspecialchars($settings['currency']); ?></span>
                                    <input type="number" step="0.01" min="0" class="form-control" id="free_shipping_threshold" name="free_shipping_threshold" value="<?php echo htmlspecialchars($settings['free_shipping_threshold']); ?>">
                                </div>
                                <small class="form-text">Set to 0 to disable free shipping</small>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Settings -->
                    <div id="seo" class="tab-content">
                        <div class="mb-3">
                            <label for="meta_title" class="form-label">Meta Title</label>
                            <input type="text" class="form-control" id="meta_title" name="meta_title" value="<?php echo htmlspecialchars($settings['meta_title']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($settings['meta_description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="meta_keywords" class="form-label">Meta Keywords</label>
                            <textarea class="form-control" id="meta_keywords" name="meta_keywords" rows="2"><?php echo htmlspecialchars($settings['meta_keywords']); ?></textarea>
                            <small class="form-text">Separate keywords with commas</small>
                        </div>
                    </div>

                    <!-- Social Media Settings -->
                    <div id="social" class="tab-content">
                        <div class="mb-3">
                            <label for="social_facebook" class="form-label">Facebook URL</label>
                            <input type="url" class="form-control" id="social_facebook" name="social_facebook" value="<?php echo htmlspecialchars($settings['social_facebook']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="social_twitter" class="form-label">Twitter URL</label>
                            <input type="url" class="form-control" id="social_twitter" name="social_twitter" value="<?php echo htmlspecialchars($settings['social_twitter']); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="social_instagram" class="form-label">Instagram URL</label>
                            <input type="url" class="form-control" id="social_instagram" name="social_instagram" value="<?php echo htmlspecialchars($settings['social_instagram']); ?>">
                        </div>
                    </div>

                    <!-- Pages Content Settings -->
                    <div id="pages" class="tab-content">
                        <div class="mb-3">
                            <label for="about_us" class="form-label">About Us Content</label>
                            <textarea class="form-control textarea-content" id="about_us" name="about_us" rows="8"><?php echo htmlspecialchars($settings['about_us']); ?></textarea>
                            <small class="form-text">HTML formatting allowed</small>
                        </div>
                        <div class="mb-3">
                            <label for="privacy_policy" class="form-label">Privacy Policy Content</label>
                            <textarea class="form-control textarea-content" id="privacy_policy" name="privacy_policy" rows="8"><?php echo htmlspecialchars($settings['privacy_policy']); ?></textarea>
                            <small class="form-text">HTML formatting allowed</small>
                        </div>
                        <div class="mb-3">
                            <label for="terms_conditions" class="form-label">Terms & Conditions Content</label>
                            <textarea class="form-control textarea-content" id="terms_conditions" name="terms_conditions" rows="8"><?php echo htmlspecialchars($settings['terms_conditions']); ?></textarea>
                            <small class="form-text">HTML formatting allowed</small>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="submit" class="btn btn-success">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Admin Sidebar JS -->
    <script src="contecnt/js/admin_sidebar.js"></script>
    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.settings-tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons and tabs
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(tab => tab.classList.remove('active'));
                    
                    // Add active class to current button and tab
                    const tabId = this.getAttribute('data-tab');
                    this.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Dismiss alerts automatically
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const closeButton = alert.querySelector('.btn-close');
                    if (closeButton) {
                        closeButton.click();
                    }
                }, 5000);
            });
        });
    </script>
</body>
</html> 