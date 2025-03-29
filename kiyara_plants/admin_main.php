<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin.php");
    exit();
}

include 'db_connection.php';

// Get counts for dashboard
$sql_orders = "SELECT COUNT(*) as total_orders, 
               SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as new_orders,
               SUM(order_total) as total_revenue 
               FROM orders";
$result_orders = mysqli_query($conn, $sql_orders);
if ($result_orders) {
    $orders_data = mysqli_fetch_assoc($result_orders);
} else {
    $orders_data = [
        'total_orders' => 0,
        'new_orders' => 0,
        'total_revenue' => 0
    ];
    // echo "Error in orders query: " . mysqli_error($conn);
}

$sql_products = "SELECT COUNT(*) as total_products, 
                SUM(CASE WHEN stock < 10 THEN 1 ELSE 0 END) as low_stock 
                FROM plants";
$result_products = mysqli_query($conn, $sql_products);
if ($result_products) {
    $products_data = mysqli_fetch_assoc($result_products);
} else {
    $products_data = [
        'total_products' => 0,
        'low_stock' => 0
    ];
    // echo "Error in products query: " . mysqli_error($conn);
}

$sql_customers = "SELECT COUNT(*) as total_customers FROM customers";
$result_customers = mysqli_query($conn, $sql_customers);
if ($result_customers) {
    $customers_data = mysqli_fetch_assoc($result_customers);
} else {
    $customers_data = [
        'total_customers' => 0
    ];
    // echo "Error in customers query: " . mysqli_error($conn);
}

$sql_categories = "SELECT COUNT(*) as total_categories FROM categories";
$result_categories = mysqli_query($conn, $sql_categories);
if ($result_categories) {
    $categories_data = mysqli_fetch_assoc($result_categories);
} else {
    $categories_data = [
        'total_categories' => 0
    ];
    // echo "Error in categories query: " . mysqli_error($conn);
}

// Get recent orders
$sql_recent_orders = "SELECT o.order_id, o.order_date, o.order_total, o.status, c.name as customer_name 
                      FROM orders o
                      JOIN customers c ON o.customer_id = c.customer_id
                      ORDER BY o.order_date DESC LIMIT 5";
$result_recent_orders = mysqli_query($conn, $sql_recent_orders);
if (!$result_recent_orders) {
    // Handle error
    $result_recent_orders = false;
    // echo "Error in recent orders query: " . mysqli_error($conn);
}

// Get top selling products
$sql_top_products = "SELECT p.id, p.name, p.image_url, COUNT(od.order_id) as order_count, SUM(od.quantity) as total_quantity
                     FROM plants p
                     JOIN order_details od ON p.id = od.plant_id
                     GROUP BY p.id
                     ORDER BY total_quantity DESC LIMIT 5";
$result_top_products = mysqli_query($conn, $sql_top_products);
if (!$result_top_products) {
    // Handle error
    $result_top_products = false;
    // echo "Error in top products query: " . mysqli_error($conn);
}

// Function to get status text
function getStatusText($status) {
    switch ($status) {
        case 1:
            return "New Order";
        case 2:
            return "Processing";
        case 3:
            return "Shipped";
        case 4:
            return "Delivered";
        case 5:
            return "Cancelled";
        default:
            return "Unknown";
    }
}

// Function to get status class
function getStatusClass($status) {
    switch ($status) {
        case 1:
            return "bg-info";
        case 2:
            return "bg-warning";
        case 3:
            return "bg-primary";
        case 4:
            return "bg-success";
        case 5:
            return "bg-danger";
        default:
            return "bg-secondary";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kiyara Plants</title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Admin Styles -->
    <link rel="stylesheet" href="contecnt/css/admin_style.css">
    <!-- Admin Sidebar Styles -->
    <link rel="stylesheet" href="contecnt/css/admin_sidebar.css">
</head>
<body>
    <!-- Include Admin Sidebar -->
    <?php include 'admin_sidebar.php'; ?>
    
    <!-- Content -->
    <div class="content">
        <!-- Navbar -->
        <nav class="navbar">
            <div class="container">
                <span class="navbar-brand">Dashboard</span>
                <div>
                    <span class="me-3">Welcome, <?php echo $_SESSION["admin_name"]; ?>!</span>
                </div>
            </div>
        </nav>
        
        <!-- Welcome Message -->
        <div class="welcome-message">
            <h4>Welcome to Kiyara Plants Admin Dashboard</h4>
            <p>Here you can manage your products, orders, customers, and view sales reports. Use the quick access cards below or the sidebar menu to navigate.</p>
        </div>
        
        <!-- Stats Row -->
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <span class="badge bg-success rounded-pill"><?php echo $orders_data['new_orders']; ?> new</span>
                    </div>
                    <h2 class="number"><?php echo number_format($orders_data['total_orders']); ?></h2>
                    <p class="label">Total Orders</p>
                    <p class="info text-muted">Revenue: $<?php echo number_format($orders_data['total_revenue'], 2); ?></p>
                    <a href="admin_orders.php" class="btn btn-outline-success mt-2">View Orders</a>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <span class="badge bg-warning rounded-pill"><?php echo $products_data['low_stock']; ?> low stock</span>
                    </div>
                    <h2 class="number"><?php echo number_format($products_data['total_products']); ?></h2>
                    <p class="label">Total Products</p>
                    <p class="info text-muted"><?php echo $categories_data['total_categories']; ?> categories</p>
                    <a href="admin_products.php" class="btn btn-outline-success mt-2">Manage Products</a>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <h2 class="number"><?php echo number_format($customers_data['total_customers']); ?></h2>
                    <p class="label">Total Customers</p>
                    <p class="info text-muted">Customer management</p>
                    <a href="admin_customers.php" class="btn btn-outline-success mt-2">View Customers</a>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <h2 class="number">Reports</h2>
                    <p class="label">Sales Analytics</p>
                    <p class="info text-muted">View detailed sales reports</p>
                    <a href="admin_reports.php" class="btn btn-outline-success mt-2">View Reports</a>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <h5 class="mb-3">Quick Actions</h5>
        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <a href="admin_products.php?action=add" class="quick-action-card">
                    <i class="fas fa-plus-circle"></i>
                    <h5>Add New Product</h5>
                </a>
            </div>
            
            <div class="col-md-3 mb-4">
                <a href="admin_categories.php?action=add" class="quick-action-card">
                    <i class="fas fa-folder-plus"></i>
                    <h5>Add New Category</h5>
                </a>
            </div>
            
            <div class="col-md-3 mb-4">
                <a href="admin_orders.php?status=1" class="quick-action-card">
                    <i class="fas fa-clipboard-list"></i>
                    <h5>View New Orders</h5>
                </a>
            </div>
            
            <div class="col-md-3 mb-4">
                <a href="admin_products.php?stock=low" class="quick-action-card">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h5>Low Stock Products</h5>
                </a>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Orders -->
            <div class="col-lg-7 mb-4">
                <div class="card">
                    <div class="card-header">
                        <span>Recent Orders</span>
                        <a href="admin_orders.php" class="btn btn-outline-success">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_recent_orders): ?>
                                        <?php while ($order = mysqli_fetch_assoc($result_recent_orders)): ?>
                                        <tr>
                                            <td>#<?php echo $order['order_id']; ?></td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                            <td>$<?php echo number_format($order['order_total'], 2); ?></td>
                                            <td><span class="status-badge <?php echo getStatusClass($order['status']); ?>"><?php echo getStatusText($order['status']); ?></span></td>
                                            <td>
                                                <a href="admin_view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                        
                                        <?php if (mysqli_num_rows($result_recent_orders) == 0): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-3">No orders found</td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-3">Error retrieving recent orders</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Selling Products -->
            <div class="col-lg-5 mb-4">
                <div class="card">
                    <div class="card-header">
                        <span>Top Selling Products</span>
                        <a href="admin_reports.php" class="btn btn-outline-success">View Reports</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Orders</th>
                                        <th>Sold</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result_top_products): ?>
                                        <?php while ($product = mysqli_fetch_assoc($result_top_products)): ?>
                                        <tr>
                                            <td class="d-flex align-items-center">
                                                <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img me-2">
                                                <span><?php echo htmlspecialchars($product['name']); ?></span>
                                            </td>
                                            <td><?php echo $product['order_count']; ?></td>
                                            <td><?php echo $product['total_quantity']; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                        
                                        <?php if (mysqli_num_rows($result_top_products) == 0): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-3">No product data available</td>
                                        </tr>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-3">Error retrieving top selling products</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Admin Sidebar JS -->
    <script src="contecnt/js/admin_sidebar.js"></script>
    <!-- Admin Modal JS -->
    <script src="contecnt/js/admin_modal.js"></script>
</body>
</html>