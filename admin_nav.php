<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
    <div class="container">
        <a class="navbar-brand" href="admin_main.php">
            <img src="contecnt/img/kiyara Plants logo.png" alt="Kiyara Plants" height="40">
            Admin Panel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="admin_main.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_products.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_orders.php">Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_categories.php">Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_customers.php">Customers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_settings.php">Settings</a>
                </li>
            </ul>
            <div class="d-flex">
                <div class="dropdown">
                    <button class="btn btn-outline-light dropdown-toggle" type="button" id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo $_SESSION['username']; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                        <li><a class="dropdown-item" href="admin_profile.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav> 