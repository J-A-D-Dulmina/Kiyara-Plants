<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin.php");
    exit();
}

include 'db_connection.php';

$success_message = '';
$error_message = '';

// Get search filter
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Prepare search query
$where_clause = "";
$params = [];
$types = "";

if (!empty($search_term)) {
    $where_clause = " WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
    $search_param = "%{$search_term}%";
    $params = [$search_param, $search_param, $search_param];
    $types = "sss";
}

// Get customers with pagination
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Count total customers
$sql_count = "SELECT COUNT(*) as total FROM customers {$where_clause}";
$stmt_count = mysqli_prepare($conn, $sql_count);

if (!empty($params)) {
    $bind_params = array_merge([$stmt_count, $types], $params);
    call_user_func_array('mysqli_stmt_bind_param', $bind_params);
}

mysqli_stmt_execute($stmt_count);
$result_count = mysqli_stmt_get_result($stmt_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_customers = $row_count['total'];
$total_pages = ceil($total_customers / $items_per_page);

// Get customers for current page
$sql_customers = "SELECT c.*, 
                    (SELECT COUNT(*) FROM orders WHERE customer_id = c.customer_id) as order_count, 
                    (SELECT SUM(total_amount) FROM orders WHERE customer_id = c.customer_id) as total_spent 
                 FROM customers c 
                 {$where_clause} 
                 ORDER BY c.customer_id DESC 
                 LIMIT ? OFFSET ?";

$stmt_customers = mysqli_prepare($conn, $sql_customers);

if (!empty($params)) {
    $params[] = $items_per_page;
    $params[] = $offset;
    $types .= "ii";
    
    $bind_params = array_merge([$stmt_customers, $types], $params);
    call_user_func_array('mysqli_stmt_bind_param', $bind_params);
} else {
    mysqli_stmt_bind_param($stmt_customers, "ii", $items_per_page, $offset);
}

mysqli_stmt_execute($stmt_customers);
$result_customers = mysqli_stmt_get_result($stmt_customers);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers - Kiyara Plants Admin</title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Admin Styles -->
    <link rel="stylesheet" href="contecnt/css/admin_style.css">
    <!-- Admin Sidebar Styles -->
    <link rel="stylesheet" href="contecnt/css/admin_sidebar.css">
    <link rel="stylesheet" href="contecnt/css/admin_customers.css">
</head>
<body>
    <!-- Include Admin Sidebar -->
    <?php include 'admin_sidebar.php'; ?>
    
    <!-- Content -->
    <div class="content">
        <!-- Navbar -->
        <nav class="navbar">
            <div class="container">
                <span class="navbar-brand">Manage Customers</span>
            </div>
        </nav>
        
        <!-- Alerts -->
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
        
        <!-- Customers Table Card -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <div class="row align-items-center">
                    <div class="col-8">
                        <h5 class="mb-0">All Customers</h5>
                    </div>
                    <div class="col-4">
                        <form method="GET" class="search-form">
                            <input type="text" class="form-control me-2" name="search" placeholder="Search customers..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button type="submit" class="btn btn-outline-success"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_customers && mysqli_num_rows($result_customers) > 0) {
                                while ($customer = mysqli_fetch_assoc($result_customers)) {
                                    echo '<tr>';
                                    echo '<td>#' . $customer['customer_id'] . '</td>';
                                    echo '<td>';
                                    echo '<div class="d-flex align-items-center">';
                                    
                                    if (!empty($customer['profile_image'])) {
                                        echo '<img src="' . $customer['profile_image'] . '" alt="Profile" class="customer-avatar me-3">';
                                    } else {
                                        echo '<div class="customer-avatar me-3 bg-light d-flex align-items-center justify-content-center"><i class="fas fa-user text-secondary"></i></div>';
                                    }
                                    
                                    echo '<div>';
                                    echo '<h6 class="mb-0">' . htmlspecialchars($customer['name']) . '</h6>';
                                    if (!empty($customer['address'])) {
                                        echo '<small class="text-muted">' . htmlspecialchars(substr($customer['address'], 0, 35)) . (strlen($customer['address']) > 35 ? '...' : '') . '</small>';
                                    }
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</td>';
                                    
                                    echo '<td>';
                                    echo '<div>' . htmlspecialchars($customer['email']) . '</div>';
                                    if (!empty($customer['phone'])) {
                                        echo '<small class="text-muted">' . htmlspecialchars($customer['phone']) . '</small>';
                                    }
                                    echo '</td>';
                                    
                                    echo '<td>' . $customer['order_count'] . '</td>';
                                    echo '<td>' . ($customer['total_spent'] ? 'Rs.' . number_format($customer['total_spent'], 2) : 'Rs.0.00') . '</td>';
                                    
                                    echo '<td>';
                                    echo '<div class="btn-group">';
                                    echo '<a href="admin_view_customer.php?id=' . $customer['customer_id'] . '" class="btn btn-primary me-1"><i class="fas fa-eye"></i></a>';
                                    echo '<a href="mailto:' . $customer['email'] . '" class="btn btn-outline-secondary me-1"><i class="fas fa-envelope"></i></a>';
                                    echo '</div>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center">No customers found.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <!-- Statistics -->
                <div class="row mt-4">
                    <div class="col-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-users text-primary mb-3" style="font-size: 24px;"></i>
                                <h6 class="mb-0">Total Customers</h6>
                                <p class="display-6 mb-0"><?php echo $total_customers; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php
                    // Get total orders by all customers
                    $sql_orders = "SELECT COUNT(*) as total FROM orders";
                    $result_orders = mysqli_query($conn, $sql_orders);
                    $total_orders = 0;
                    if ($result_orders) {
                        $row_orders = mysqli_fetch_assoc($result_orders);
                        $total_orders = $row_orders['total'];
                    }
                    ?>
                    
                    <div class="col-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-shopping-cart text-success mb-3" style="font-size: 24px;"></i>
                                <h6 class="mb-0">Total Orders</h6>
                                <p class="display-6 mb-0"><?php echo $total_orders; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <?php
                    // Get total revenue from all customers
                    $sql_revenue = "SELECT SUM(total_amount) as total FROM orders";
                    $result_revenue = mysqli_query($conn, $sql_revenue);
                    $total_revenue = 0;
                    if ($result_revenue) {
                        $row_revenue = mysqli_fetch_assoc($result_revenue);
                        $total_revenue = $row_revenue['total'] ? $row_revenue['total'] : 0;
                    }
                    ?>
                    
                    <div class="col-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-dollar-sign text-warning mb-3" style="font-size: 24px;"></i>
                                <h6 class="mb-0">Total Revenue</h6>
                                <p class="display-6 mb-0">Rs.<?php echo number_format($total_revenue, 2); ?></p>
                            </div>
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