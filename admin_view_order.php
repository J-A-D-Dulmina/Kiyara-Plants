<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin.php");
    exit();
}

include 'db_connection.php';

$error_message = '';

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin_orders.php");
    exit();
}

$order_id = $_GET['id'];

// Get order details
$sql_order = "SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone, c.address as customer_address 
              FROM orders o
              JOIN customers c ON o.customer_id = c.customer_id
              WHERE o.order_id = ?";
$stmt_order = mysqli_prepare($conn, $sql_order);
mysqli_stmt_bind_param($stmt_order, "i", $order_id);
mysqli_stmt_execute($stmt_order);
$result_order = mysqli_stmt_get_result($stmt_order);

if (mysqli_num_rows($result_order) == 0) {
    $error_message = "Order not found.";
} else {
    $order = mysqli_fetch_assoc($result_order);
    
    // Get order items
    $sql_items = "SELECT od.*, p.name as product_name, p.pot_type, p.img_url
                  FROM order_details od
                  JOIN plants p ON od.plant_id = p.plant_id
                  WHERE od.order_id = ?";
    $stmt_items = mysqli_prepare($conn, $sql_items);
    mysqli_stmt_bind_param($stmt_items, "i", $order_id);
    mysqli_stmt_execute($stmt_items);
    $result_items = mysqli_stmt_get_result($stmt_items);
    
    // Get shipping updates if any
    $sql_updates = "SELECT * FROM shipping_updates 
                   WHERE order_id = ? 
                   ORDER BY update_date DESC";
    $stmt_updates = mysqli_prepare($conn, $sql_updates);
    mysqli_stmt_bind_param($stmt_updates, "i", $order_id);
    mysqli_stmt_execute($stmt_updates);
    $result_updates = mysqli_stmt_get_result($stmt_updates);
}

// Function to determine status text
function getStatusText($status_code) {
    switch ($status_code) {
        case 0:
            return "Pending";
        case 1:
            return "Processing";
        case 2:
            return "Shipped";
        case 3:
            return "Delivered";
        case 4:
            return "Cancelled";
        default:
            return "Unknown";
    }
}

// Function to determine status class
function getStatusClass($status_code) {
    switch ($status_code) {
        case 0:
            return "bg-warning text-dark";
        case 1:
            return "bg-info text-white";
        case 2:
            return "bg-primary text-white";
        case 3:
            return "bg-success text-white";
        case 4:
            return "bg-danger text-white";
        default:
            return "bg-secondary text-white";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Kiyara Plants Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom Admin Styles -->
    <link rel="stylesheet" href="contecnt/css/admin_style.css">
    <link rel="stylesheet" href="contecnt/css/admin_view_order.css">
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
            <li><a href="admin_orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="admin_customers.php"><i class="fas fa-users"></i> Customers</a></li>
            <li><a href="admin_categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="admin_profile.php"><i class="fas fa-user-cog"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <!-- Content -->
    <div class="content">
        <!-- Navbar -->
        <nav class="navbar navbar-light mb-4">
            <div class="container-fluid">
                <a href="admin_orders.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-2"></i> Back to Orders
                </a>
                
                <?php if (!$error_message): ?>
                <div>
                    <a href="admin_update_order.php?id=<?php echo $order_id; ?>" class="btn btn-success me-2">
                        <i class="fas fa-edit me-1"></i> Update Status
                    </a>
                    <a href="#" class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </nav>
        
        <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
        <?php else: ?>
        
        <!-- Order Details -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Order #<?php echo $order_id; ?></h5>
                            <span class="status-badge <?php echo getStatusClass($order['shipping_status']); ?>">
                                <?php echo getStatusText($order['shipping_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted">Order Information</h6>
                                <p><strong>Order Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></p>
                                <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                                <p><strong>Order Total:</strong> Rs.<?php echo number_format($order['total_amount'], 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Customer Information</h6>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($order['customer_address']); ?></p>
                            </div>
                        </div>
                        
                        <h6 class="text-muted mb-3">Order Items</h6>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $total = 0;
                                    if ($result_items && mysqli_num_rows($result_items) > 0) {
                                        while ($item = mysqli_fetch_assoc($result_items)) {
                                            $subtotal = $item['quantity'] * ($item['subtotal'] / $item['quantity']);
                                            $total += $subtotal;
                                            
                                            echo '<tr>';
                                            echo '<td>';
                                            echo '<div class="d-flex align-items-center">';
                                            echo '<img src="' . $item['img_url'] . '" alt="' . htmlspecialchars($item['product_name']) . '" class="product-img me-3">';
                                            echo '<div>';
                                            echo '<h6 class="mb-0">' . htmlspecialchars($item['product_name']) . '</h6>';
                                            echo '<small class="text-muted">' . htmlspecialchars($item['pot_type']) . '</small>';
                                            echo '</div>';
                                            echo '</div>';
                                            echo '</td>';
                                            echo '<td>Rs.' . number_format($item['subtotal'] / $item['quantity'], 2) . '</td>';
                                            echo '<td>' . $item['quantity'] . '</td>';
                                            echo '<td>Rs.' . number_format($subtotal, 2) . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center">No items found.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                        <td><strong>Rs.<?php echo number_format($total, 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Order Timeline</h5>
                    </div>
                    <div class="card-body">
                        <ul class="timeline">
                            <?php
                            if ($result_updates && mysqli_num_rows($result_updates) > 0) {
                                while ($update = mysqli_fetch_assoc($result_updates)) {
                                    echo '<li class="timeline-item">';
                                    echo '<div class="d-flex justify-content-between">';
                                    echo '<h6 class="mb-1">' . htmlspecialchars($update['update_description']) . '</h6>';
                                    echo '<small class="text-muted">' . date('M d, g:i a', strtotime($update['update_date'])) . '</small>';
                                    echo '</div>';
                                    echo '<p class="text-muted mb-0 small">' . ($update['updated_by_admin'] ? 'Updated by admin' : 'System update') . '</p>';
                                    echo '</li>';
                                }
                            } else {
                                echo '<li class="timeline-item">';
                                echo '<div class="d-flex justify-content-between">';
                                echo '<h6 class="mb-1">Order Placed</h6>';
                                echo '<small class="text-muted">' . date('M d, g:i a', strtotime($order['order_date'])) . '</small>';
                                echo '</div>';
                                echo '<p class="text-muted mb-0 small">Order was created</p>';
                                echo '</li>';
                            }
                            ?>
                        </ul>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="admin_update_order.php?id=<?php echo $order_id; ?>" class="btn btn-success">
                                <i class="fas fa-edit me-2"></i> Update Status
                            </a>
                            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print me-2"></i> Print Order
                            </button>
                            <a href="mailto:<?php echo $order['customer_email']; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-envelope me-2"></i> Email Customer
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 