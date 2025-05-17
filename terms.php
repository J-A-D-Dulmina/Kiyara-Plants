<?php
session_start();
include 'db_connection.php';

// Get site settings
$settings = array(
    'store_name' => 'Kiyara Plants',
    'terms_conditions' => '<h1>Terms and Conditions</h1>
    <p>Welcome to Kiyara Plants. By using our website, you agree to these terms and conditions.</p>
    <h2>Product Information</h2>
    <p>We strive to provide accurate product information, but we do not warrant that product descriptions or other content is accurate, complete, reliable, or error-free.</p>
    <h2>Shipping and Delivery</h2>
    <p>Delivery times are estimates and not guaranteed. We are not responsible for delays beyond our control.</p>
    <h2>Returns and Refunds</h2>
    <p>You may return new, unopened items within 30 days of delivery for a full refund. We will process your refund within 14 days of receiving the returned product.</p>'
);

// Check if settings table exists and get settings
$settings_check = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
if (mysqli_num_rows($settings_check) > 0) {
    $settings_query = mysqli_query($conn, "SELECT * FROM settings WHERE id = 1");
    if ($settings_query && mysqli_num_rows($settings_query) > 0) {
        $db_settings = mysqli_fetch_assoc($settings_query);
        // Update settings with database values
        foreach ($db_settings as $key => $value) {
            if (array_key_exists($key, $settings) && !empty($value)) {
                $settings[$key] = $value;
            }
        }
    }
}

// Include header
include 'header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <?php 
            // Display terms and conditions content from settings
            if (!empty($settings['terms_conditions'])) {
                echo $settings['terms_conditions'];
            } else {
                echo '<div class="alert alert-info">Terms and conditions content has not been set up yet.</div>';
            }
            ?>
        </div>
    </div>
</div>

<style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }
    .py-5 {
        padding-top: 3rem;
        padding-bottom: 3rem;
    }
    .row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -15px;
        margin-left: -15px;
    }
    .col-12 {
        flex: 0 0 100%;
        max-width: 100%;
        padding-right: 15px;
        padding-left: 15px;
    }
    h1, h2, h3 {
        margin-bottom: 1rem;
        color: #287b7b;
    }
    p {
        margin-bottom: 1.5rem;
        line-height: 1.6;
    }
    .alert {
        position: relative;
        padding: 0.75rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: 0.25rem;
    }
    .alert-info {
        color: #0c5460;
        background-color: #d1ecf1;
        border-color: #bee5eb;
    }
</style>

<?php
// Include footer
include 'footer.php';
?> 