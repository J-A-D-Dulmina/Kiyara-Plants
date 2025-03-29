<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin.php");
    exit();
}

include 'db_connection.php';

// Set default date range (last 30 days)
$default_end_date = date('Y-m-d');
$default_start_date = date('Y-m-d', strtotime('-30 days'));

// Initialize filter variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $default_start_date;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $default_end_date;
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : '';

// Format dates for display
$formatted_start_date = date('F j, Y', strtotime($start_date));
$formatted_end_date = date('F j, Y', strtotime($end_date));

// Get all categories for filter dropdown
$sql_categories = "SELECT * FROM categories ORDER BY category_name";
$result_categories = mysqli_query($conn, $sql_categories);

// Build query conditions based on filters
$conditions = " WHERE o.order_date BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
$params = array($start_date, $end_date);
$types = "ss";

if (!empty($category_id)) {
    $conditions .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

// Prepare and execute sales summary query
$sql_sales_summary = "
    SELECT 
        COUNT(DISTINCT o.order_id) AS total_orders,
        COUNT(DISTINCT o.customer_id) AS unique_customers,
        SUM(o.order_total) AS total_revenue,
        AVG(o.order_total) AS average_order_value,
        SUM(od.quantity) AS total_items_sold
    FROM orders o
    JOIN order_details od ON o.order_id = od.order_id
    JOIN plants p ON od.plant_id = p.id" . $conditions;

$stmt_summary = mysqli_prepare($conn, $sql_sales_summary);
mysqli_stmt_bind_param($stmt_summary, $types, ...$params);
mysqli_stmt_execute($stmt_summary);
$result_summary = mysqli_stmt_get_result($stmt_summary);
$sales_summary = mysqli_fetch_assoc($result_summary);

// Prepare and execute top products query
$sql_top_products = "
    SELECT 
        p.id, 
        p.name, 
        p.image_url, 
        p.price,
        SUM(od.quantity) AS total_sold,
        SUM(od.unit_price * od.quantity) AS total_revenue
    FROM order_details od
    JOIN plants p ON od.plant_id = p.id
    JOIN orders o ON od.order_id = o.order_id" . $conditions . "
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5";

$stmt_top_products = mysqli_prepare($conn, $sql_top_products);
mysqli_stmt_bind_param($stmt_top_products, $types, ...$params);
mysqli_stmt_execute($stmt_top_products);
$result_top_products = mysqli_stmt_get_result($stmt_top_products);

// Prepare and execute sales by category query
$sql_sales_by_category = "
    SELECT 
        c.category_id,
        c.category_name,
        COUNT(DISTINCT od.order_id) AS order_count,
        SUM(od.quantity) AS total_quantity,
        SUM(od.unit_price * od.quantity) AS total_revenue
    FROM order_details od
    JOIN plants p ON od.plant_id = p.id
    JOIN categories c ON p.category_id = c.category_id
    JOIN orders o ON od.order_id = o.order_id" . $conditions . "
    GROUP BY c.category_id
    ORDER BY total_revenue DESC";

$stmt_sales_by_category = mysqli_prepare($conn, $sql_sales_by_category);
mysqli_stmt_bind_param($stmt_sales_by_category, $types, ...$params);
mysqli_stmt_execute($stmt_sales_by_category);
$result_sales_by_category = mysqli_stmt_get_result($stmt_sales_by_category);

// Prepare and execute daily sales data for chart
$sql_daily_sales = "
    SELECT 
        DATE(o.order_date) AS sale_date, 
        COUNT(DISTINCT o.order_id) AS order_count,
        SUM(o.order_total) AS daily_revenue
    FROM orders o" . str_replace('p.category_id', 'o.order_id', $conditions) . "
    GROUP BY DATE(o.order_date)
    ORDER BY sale_date";

$stmt_daily_sales = mysqli_prepare($conn, $sql_daily_sales);
$daily_sales_types = "ss" . (strpos($types, "i") !== false ? "i" : "");
$daily_sales_params = array_slice($params, 0, strlen($daily_sales_types));
mysqli_stmt_bind_param($stmt_daily_sales, $daily_sales_types, ...$daily_sales_params);
mysqli_stmt_execute($stmt_daily_sales);
$result_daily_sales = mysqli_stmt_get_result($stmt_daily_sales);

// Prepare daily sales data for chart
$sale_dates = array();
$daily_revenues = array();
$order_counts = array();

while ($row = mysqli_fetch_assoc($result_daily_sales)) {
    $sale_dates[] = date('M d', strtotime($row['sale_date']));
    $daily_revenues[] = $row['daily_revenue'];
    $order_counts[] = $row['order_count'];
}

$chart_data = [
    'labels' => $sale_dates,
    'revenues' => $daily_revenues,
    'orders' => $order_counts
];
$chart_json = json_encode($chart_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - Kiyara Plants Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Admin Styles -->
    <link rel="stylesheet" href="contecnt/css/admin_style.css">
    <link rel="stylesheet" href="contecnt/css/admin_reports.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <img src="contecnt/img/kiyara Plants logo.png" alt="Kiyara Plants Logo">
            <h5 class="mt-2">Admin Dashboard</h5>
        </div>
        
        <ul class="sidebar-menu">
            <li><a href="admin_main.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="admin_products.php"><i class="fas fa-leaf"></i> Products</a></li>
            <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="admin_customers.php"><i class="fas fa-users"></i> Customers</a></li>
            <li><a href="admin_categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="admin_reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
            <li><a href="admin_profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <!-- Content -->
    <div class="content">
        <!-- Navbar -->
        <nav class="navbar navbar-light mb-4">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">Sales Reports</span>
            </div>
        </nav>
        
        <!-- Filters -->
        <div class="card filter-card mb-4">
            <form method="GET" class="row align-items-end">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">All Categories</option>
                        <?php while ($category = mysqli_fetch_assoc($result_categories)): ?>
                        <option value="<?php echo $category['category_id']; ?>" <?php echo $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-filter me-2"></i>Apply Filters
                    </button>
                </div>
            </form>
        </div>
        
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Showing sales data from <strong><?php echo $formatted_start_date; ?></strong> to <strong><?php echo $formatted_end_date; ?></strong>
            <?php if (!empty($category_id)): ?>
            for selected category
            <?php endif; ?>
        </div>
        
        <!-- Stats Row -->
        <div class="row">
            <div class="col-md-3">
                <div class="card stats-card mb-4">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="me-3">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div>
                            <p class="number"><?php echo number_format($sales_summary['total_orders']); ?></p>
                            <p class="title">Total Orders</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card mb-4">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="me-3">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div>
                            <p class="number">$<?php echo number_format($sales_summary['total_revenue'], 2); ?></p>
                            <p class="title">Total Revenue</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card mb-4">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="me-3">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div>
                            <p class="number"><?php echo number_format($sales_summary['unique_customers']); ?></p>
                            <p class="title">Customers</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card mb-4">
                    <div class="card-body d-flex align-items-center p-4">
                        <div class="me-3">
                            <i class="fas fa-box"></i>
                        </div>
                        <div>
                            <p class="number"><?php echo number_format($sales_summary['total_items_sold']); ?></p>
                            <p class="title">Items Sold</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Sales Chart -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Sales Overview</span>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-secondary active" id="revenueBtn">Revenue</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="ordersBtn">Orders</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Average Order Value -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <span>Order Metrics</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-4 text-center">
                            <h6 class="text-muted mb-2">Average Order Value</h6>
                            <h2 class="mb-0">$<?php echo number_format($sales_summary['average_order_value'], 2); ?></h2>
                        </div>
                        
                        <div class="mb-4 text-center">
                            <h6 class="text-muted mb-2">Items Per Order</h6>
                            <h2 class="mb-0">
                                <?php 
                                $items_per_order = $sales_summary['total_orders'] > 0 ? 
                                    $sales_summary['total_items_sold'] / $sales_summary['total_orders'] : 0;
                                echo number_format($items_per_order, 1);
                                ?>
                            </h2>
                        </div>
                        
                        <div class="mb-4 text-center">
                            <h6 class="text-muted mb-2">Revenue Per Customer</h6>
                            <h2 class="mb-0">
                                $<?php 
                                $revenue_per_customer = $sales_summary['unique_customers'] > 0 ? 
                                    $sales_summary['total_revenue'] / $sales_summary['unique_customers'] : 0;
                                echo number_format($revenue_per_customer, 2);
                                ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Top Products -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Top Selling Products</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($product = mysqli_fetch_assoc($result_top_products)): ?>
                                    <tr>
                                        <td class="d-flex align-items-center">
                                            <img src="<?php echo $product['image_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img me-2">
                                            <span><?php echo htmlspecialchars($product['name']); ?></span>
                                        </td>
                                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                                        <td><?php echo number_format($product['total_sold']); ?></td>
                                        <td>$<?php echo number_format($product['total_revenue'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php if (mysqli_num_rows($result_top_products) == 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3">No products sold in this period</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sales by Category -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Sales by Category</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Orders</th>
                                        <th>Items</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_category_revenue = 0;
                                    while ($category = mysqli_fetch_assoc($result_sales_by_category)): 
                                        $total_category_revenue += $category['total_revenue'];
                                    endwhile;
                                    
                                    // Reset the result pointer
                                    mysqli_data_seek($result_sales_by_category, 0);
                                    
                                    while ($category = mysqli_fetch_assoc($result_sales_by_category)): 
                                        $percentage = $total_category_revenue > 0 ? 
                                            ($category['total_revenue'] / $total_category_revenue) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                        <td><?php echo number_format($category['order_count']); ?></td>
                                        <td><?php echo number_format($category['total_quantity']); ?></td>
                                        <td>
                                            $<?php echo number_format($category['total_revenue'], 2); ?>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%" 
                                                     aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    
                                    <?php if (mysqli_num_rows($result_sales_by_category) == 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3">No category data available for this period</td>
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
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Parse chart data from PHP
        const chartData = <?php echo $chart_json; ?>;
        
        // Initialize chart
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Revenue',
                    data: chartData.revenues,
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    borderColor: 'rgba(76, 175, 80, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.raw.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
        
        // Switch between revenue and order count
        document.getElementById('revenueBtn').addEventListener('click', function() {
            document.getElementById('revenueBtn').classList.add('active');
            document.getElementById('ordersBtn').classList.remove('active');
            
            salesChart.data.datasets[0].label = 'Revenue';
            salesChart.data.datasets[0].data = chartData.revenues;
            salesChart.options.scales.y.ticks.callback = function(value) {
                return '$' + value;
            };
            salesChart.options.plugins.tooltip.callbacks.label = function(context) {
                return '$' + context.raw.toFixed(2);
            };
            salesChart.update();
        });
        
        document.getElementById('ordersBtn').addEventListener('click', function() {
            document.getElementById('ordersBtn').classList.add('active');
            document.getElementById('revenueBtn').classList.remove('active');
            
            salesChart.data.datasets[0].label = 'Orders';
            salesChart.data.datasets[0].data = chartData.orders;
            salesChart.options.scales.y.ticks.callback = function(value) {
                return value;
            };
            salesChart.options.plugins.tooltip.callbacks.label = function(context) {
                return context.raw;
            };
            salesChart.update();
        });
    </script>
</body>
</html> 