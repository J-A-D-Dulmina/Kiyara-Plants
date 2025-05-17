<?php
// Get the current page filename to highlight the active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-brand">
        <img src="contecnt/img/kiyara Plants logo.png" alt="Kiyara Plants Logo">
        <h5 class="mt-2">Admin Dashboard</h5>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="admin_main.php" <?php echo ($current_page == 'admin_main.php') ? 'class="active"' : ''; ?>><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="admin_products.php" <?php echo ($current_page == 'admin_products.php' || $current_page == 'admin_add_product.php' || $current_page == 'admin_edit_product.php') ? 'class="active"' : ''; ?>><i class="fas fa-leaf"></i> Products</a></li>
        <li><a href="admin_orders.php" <?php echo ($current_page == 'admin_orders.php' || $current_page == 'admin_view_order.php' || $current_page == 'admin_update_order.php') ? 'class="active"' : ''; ?>><i class="fas fa-shopping-cart"></i> Orders</a></li>
        <li><a href="admin_customers.php" <?php echo ($current_page == 'admin_customers.php' || $current_page == 'admin_view_customer.php') ? 'class="active"' : ''; ?>><i class="fas fa-users"></i> Customers</a></li>
        <li><a href="admin_categories.php" <?php echo ($current_page == 'admin_categories.php') ? 'class="active"' : ''; ?>><i class="fas fa-tags"></i> Categories</a></li>
        <li><a href="admin_reports.php" <?php echo ($current_page == 'admin_reports.php') ? 'class="active"' : ''; ?>><i class="fas fa-chart-bar"></i> Reports</a></li>
        <li><a href="admin_settings.php" <?php echo ($current_page == 'admin_settings.php') ? 'class="active"' : ''; ?>><i class="fas fa-cog"></i> Settings</a></li>
        <li><a href="admin_profile.php" <?php echo ($current_page == 'admin_profile.php') ? 'class="active"' : ''; ?>><i class="fas fa-user-cog"></i> Profile</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div> 