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

// Update order status if form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    
    // Update order status in orders table
    $sql_update_order = "UPDATE orders SET shipping_status = ? WHERE order_id = ?";
    $stmt_update_order = mysqli_prepare($conn, $sql_update_order);
    mysqli_stmt_bind_param($stmt_update_order, "ii", $new_status, $order_id);
    
    if (mysqli_stmt_execute($stmt_update_order)) {
        // Update status in order_details table too
        $sql_update_details = "UPDATE order_details SET shipping_status = ? WHERE order_id = ?";
        $stmt_update_details = mysqli_prepare($conn, $sql_update_details);
        mysqli_stmt_bind_param($stmt_update_details, "ii", $new_status, $order_id);
        mysqli_stmt_execute($stmt_update_details);
        
        // Add update to shipping_updates table
        $admin_id = $_SESSION["admin_id"];
        $update_description = getStatusText($new_status);
        
        $sql_add_update = "INSERT INTO shipping_updates (order_id, update_date, update_description, updated_by_admin) 
                          VALUES (?, NOW(), ?, 1)";
        $stmt_add_update = mysqli_prepare($conn, $sql_add_update);
        mysqli_stmt_bind_param($stmt_add_update, "is", $order_id, $update_description);
        mysqli_stmt_execute($stmt_add_update);
        
        $success_message = "Order #" . $order_id . " status updated successfully!";
    } else {
        $error_message = "Error updating order status. Please try again.";
    }
}

// Function to get status text
function getStatusText($status_code) {
    switch ($status_code) {
        case 0:
            return "Order placed";
        case 1:
            return "Processing order";
        case 2:
            return "Order shipped";
        case 3:
            return "Order delivered";
        case 4:
            return "Order cancelled";
        default:
            return "Status updated";
    }
}

// Get orders with pagination
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Count total orders
$sql_count = "SELECT COUNT(*) as total FROM orders";
$result_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_orders = $row_count['total'];
$total_pages = ceil($total_orders / $items_per_page);

// Get search filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Prepare search query
$where_clause = "";
$params = [];
$types = "";

if ($status_filter !== '' && $status_filter !== 'all') {
    $where_clause .= " WHERE o.shipping_status = ?";
    $params[] = $status_filter;
    $types .= "i";
}

if (!empty($search_term)) {
    if (empty($where_clause)) {
        $where_clause .= " WHERE ";
    } else {
        $where_clause .= " AND ";
    }
    $where_clause .= "(o.order_id LIKE ? OR c.name LIKE ? OR c.email LIKE ?)";
    $search_param = "%{$search_term}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Get orders for current page with filters
$sql_orders = "SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone 
              FROM orders o
              JOIN customers c ON o.customer_id = c.customer_id
              {$where_clause}
              ORDER BY o.order_date DESC
              LIMIT ? OFFSET ?";

$stmt_orders = mysqli_prepare($conn, $sql_orders);

if (!empty($params)) {
    $params[] = $items_per_page;
    $params[] = $offset;
    $types .= "ii";
    
    $bind_params = array_merge([$stmt_orders, $types], $params);
    call_user_func_array('mysqli_stmt_bind_param', $bind_params);
} else {
    mysqli_stmt_bind_param($stmt_orders, "ii", $items_per_page, $offset);
}

mysqli_stmt_execute($stmt_orders);
$result_orders = mysqli_stmt_get_result($stmt_orders);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Kiyara Plants Admin</title>
    
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
                <span class="navbar-brand">Manage Orders</span>
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
        
        <!-- Orders Table Card -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <div class="row align-items-center">
                    <div class="col-8">
                        <h5 class="mb-0">All Orders</h5>
                    </div>
                    <div class="col-4">
                        <form method="GET" class="search-form">
                            <input type="text" class="form-control me-2" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search_term); ?>">
                            <button type="submit" class="btn btn-outline-success"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="mb-4">
                    <form method="GET" class="row g-3">
                        <div class="col-4">
                            <label for="status" class="form-label">Filter by Status</label>
                            <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                                <option value="all" <?php echo ($status_filter === '' || $status_filter === 'all') ? 'selected' : ''; ?>>All Orders</option>
                                <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Pending</option>
                                <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Processing</option>
                                <option value="2" <?php echo $status_filter === '2' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="3" <?php echo $status_filter === '3' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="4" <?php echo $status_filter === '4' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <?php if (!empty($search_term)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                        <?php endif; ?>
                        
                        <div class="col-2 d-flex align-items-end">
                            <a href="admin_orders.php" class="btn btn-outline-secondary">Clear Filters</a>
                        </div>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result_orders && mysqli_num_rows($result_orders) > 0) {
                                while ($order = mysqli_fetch_assoc($result_orders)) {
                                    // Determine status class and text
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    switch ($order['shipping_status']) {
                                        case 0:
                                            $status_class = 'bg-warning text-dark';
                                            $status_text = 'Pending';
                                            break;
                                        case 1:
                                            $status_class = 'bg-info text-white';
                                            $status_text = 'Processing';
                                            break;
                                        case 2:
                                            $status_class = 'bg-primary text-white';
                                            $status_text = 'Shipped';
                                            break;
                                        case 3:
                                            $status_class = 'bg-success text-white';
                                            $status_text = 'Delivered';
                                            break;
                                        case 4:
                                            $status_class = 'bg-danger text-white';
                                            $status_text = 'Cancelled';
                                            break;
                                        default:
                                            $status_class = 'bg-secondary text-white';
                                            $status_text = 'Unknown';
                                    }
                                    
                                    echo '<tr>';
                                    echo '<td>#' . $order['order_id'] . '</td>';
                                    echo '<td>';
                                    echo '<div>' . htmlspecialchars($order['customer_name']) . '</div>';
                                    echo '<small class="text-muted">' . htmlspecialchars($order['customer_email']) . '</small>';
                                    echo '</td>';
                                    echo '<td>' . date('M d, Y', strtotime($order['order_date'])) . '</td>';
                                    echo '<td>Rs.' . number_format($order['total_amount'], 2) . '</td>';
                                    echo '<td><span class="status-badge ' . $status_class . '">' . $status_text . '</span></td>';
                                    echo '<td>';
                                    echo '<div class="btn-group">';
                                    echo '<a href="admin_view_order.php?id=' . $order['order_id'] . '" class="btn btn-primary me-1"><i class="fas fa-eye"></i></a>';
                                    echo '<button type="button" class="btn btn-success" data-toggle="modal" data-target="#updateModal' . $order['order_id'] . '"><i class="fas fa-edit"></i></button>';
                                    echo '</div>';
                                    
                                    // Status update modal
                                    echo '<div class="modal" id="updateModal' . $order['order_id'] . '" tabindex="-1" role="dialog" aria-labelledby="updateModalLabel" aria-hidden="true">';
                                    echo '<div class="modal-dialog">';
                                    echo '<div class="modal-content">';
                                    echo '<div class="modal-header">';
                                    echo '<h5 class="modal-title" id="updateModalLabel">Update Order Status</h5>';
                                    echo '<button type="button" class="btn-close" data-dismiss="modal" aria-label="Close">&times;</button>';
                                    echo '</div>';
                                    echo '<div class="modal-body">';
                                    echo '<form method="POST">';
                                    echo '<input type="hidden" name="order_id" value="' . $order['order_id'] . '">';
                                    echo '<div class="mb-3">';
                                    echo '<label for="new_status' . $order['order_id'] . '" class="form-label">Status</label>';
                                    echo '<select class="form-select" id="new_status' . $order['order_id'] . '" name="new_status">';
                                    echo '<option value="0" ' . ($order['shipping_status'] == 0 ? 'selected' : '') . '>Pending</option>';
                                    echo '<option value="1" ' . ($order['shipping_status'] == 1 ? 'selected' : '') . '>Processing</option>';
                                    echo '<option value="2" ' . ($order['shipping_status'] == 2 ? 'selected' : '') . '>Shipped</option>';
                                    echo '<option value="3" ' . ($order['shipping_status'] == 3 ? 'selected' : '') . '>Delivered</option>';
                                    echo '<option value="4" ' . ($order['shipping_status'] == 4 ? 'selected' : '') . '>Cancelled</option>';
                                    echo '</select>';
                                    echo '</div>';
                                    echo '<div class="d-grid">';
                                    echo '<button type="submit" name="update_status" class="btn btn-success">Update Status</button>';
                                    echo '</div>';
                                    echo '</form>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center">No orders found.</td></tr>';
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
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo (!empty($status_filter) && $status_filter !== 'all') ? '&status=' . $status_filter : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo (!empty($status_filter) && $status_filter !== 'all') ? '&status=' . $status_filter : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo (!empty($status_filter) && $status_filter !== 'all') ? '&status=' . $status_filter : ''; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Admin Sidebar JS -->
    <script src="contecnt/js/admin_sidebar.js"></script>
    <!-- Admin Modal JS -->
    <script src="contecnt/js/admin_modal.js"></script>
</body>
</html> 