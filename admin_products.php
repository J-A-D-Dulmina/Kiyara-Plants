<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin.php");
    exit();
}

include 'db_connection.php';

// Process form submissions
$success_message = '';
$error_message = '';

// Handle product deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $plant_id = $_GET['delete'];
    
    // Delete from plant_category
    $sql_delete_category = "DELETE FROM plant_category WHERE plant_id = ?";
    $stmt_delete_category = mysqli_prepare($conn, $sql_delete_category);
    mysqli_stmt_bind_param($stmt_delete_category, "i", $plant_id);
    mysqli_stmt_execute($stmt_delete_category);
    
    // Delete from plant_images
    $sql_delete_images = "DELETE FROM plant_images WHERE plant_id = ?";
    $stmt_delete_images = mysqli_prepare($conn, $sql_delete_images);
    mysqli_stmt_bind_param($stmt_delete_images, "i", $plant_id);
    mysqli_stmt_execute($stmt_delete_images);
    
    // Delete the plant
    $sql_delete_plant = "DELETE FROM plants WHERE plant_id = ?";
    $stmt_delete_plant = mysqli_prepare($conn, $sql_delete_plant);
    mysqli_stmt_bind_param($stmt_delete_plant, "i", $plant_id);
    
    if (mysqli_stmt_execute($stmt_delete_plant)) {
        $success_message = "Product deleted successfully!";
    } else {
        $error_message = "Error deleting product. Please try again.";
    }
}

// Handle status toggle
if (isset($_GET['toggle_status']) && !empty($_GET['toggle_status'])) {
    $plant_id = $_GET['toggle_status'];
    
    // Get current status
    $sql_get_status = "SELECT status FROM plants WHERE plant_id = ?";
    $stmt_get_status = mysqli_prepare($conn, $sql_get_status);
    mysqli_stmt_bind_param($stmt_get_status, "i", $plant_id);
    mysqli_stmt_execute($stmt_get_status);
    $result_status = mysqli_stmt_get_result($stmt_get_status);
    $row_status = mysqli_fetch_assoc($result_status);
    
    // Toggle status
    $new_status = $row_status['status'] ? 0 : 1;
    
    $sql_update_status = "UPDATE plants SET status = ? WHERE plant_id = ?";
    $stmt_update_status = mysqli_prepare($conn, $sql_update_status);
    mysqli_stmt_bind_param($stmt_update_status, "ii", $new_status, $plant_id);
    
    if (mysqli_stmt_execute($stmt_update_status)) {
        $status_text = $new_status ? "activated" : "deactivated";
        $success_message = "Product {$status_text} successfully!";
    } else {
        $error_message = "Error updating product status. Please try again.";
    }
}

// Get all products with pagination
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Count total products
$sql_count = "SELECT COUNT(*) as total FROM plants";
$result_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_products = $row_count['total'];
$total_pages = ceil($total_products / $items_per_page);

// Get products for current page
$sql_products = "SELECT p.*, 
                (SELECT pi.image_url FROM plant_images pi WHERE pi.plant_id = p.plant_id AND pi.is_main = 1 LIMIT 1) as main_image_url,
                GROUP_CONCAT(c.name SEPARATOR ', ') as categories 
                FROM plants p 
                LEFT JOIN plant_category pc ON p.plant_id = pc.plant_id 
                LEFT JOIN categories c ON pc.category_id = c.category_id 
                GROUP BY p.plant_id
                ORDER BY p.plant_id DESC
                LIMIT ? OFFSET ?";
$stmt_products = mysqli_prepare($conn, $sql_products);
mysqli_stmt_bind_param($stmt_products, "ii", $items_per_page, $offset);
mysqli_stmt_execute($stmt_products);
$result_products = mysqli_stmt_get_result($stmt_products);

// Get categories for dropdown
$sql_categories = "SELECT * FROM categories ORDER BY name";
$result_categories = mysqli_query($conn, $sql_categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Kiyara Plants Admin</title>
    
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
                <span class="navbar-brand">Manage Products</span>
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
        
        <!-- Products Table Card -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">All Products</h5>
                <a href="admin_add_product.php" class="btn btn-success">
                    <i class="fas fa-plus me-2"></i>Add New Product
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Categories</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($result_products) > 0) {
                                while ($product = mysqli_fetch_assoc($result_products)) {
                                    echo '<tr>';
                                    echo '<td>#' . $product['plant_id'] . '</td>';
                                    echo '<td><img src="' . (!empty($product['main_image_url']) ? $product['main_image_url'] : $product['img_url']) . '" alt="' . $product['name'] . '" class="product-img"></td>';
                                    echo '<td>' . htmlspecialchars($product['name']) . '</td>';
                                    echo '<td>Rs.' . number_format($product['price'], 2) . '</td>';
                                    echo '<td>' . $product['stock'] . '</td>';
                                    echo '<td>' . ($product['categories'] ? htmlspecialchars($product['categories']) : 'Uncategorized') . '</td>';
                                    
                                    $status_class = $product['status'] ? 'bg-success' : 'bg-danger';
                                    $status_text = $product['status'] ? 'Active' : 'Inactive';
                                    
                                    echo '<td><span class="status-badge ' . $status_class . ' text-white">' . $status_text . '</span></td>';
                                    
                                    echo '<td>';
                                    echo '<div class="btn-group">';
                                    echo '<a href="admin_edit_product.php?id=' . $product['plant_id'] . '" class="btn btn-primary me-1"><i class="fas fa-edit"></i></a>';
                                    echo '<a href="admin_products.php?toggle_status=' . $product['plant_id'] . '" class="btn btn-warning me-1" title="Toggle Status"><i class="fas fa-power-off"></i></a>';
                                    echo '<a href="#" data-toggle="modal" data-target="#deleteModal' . $product['plant_id'] . '" class="btn btn-danger"><i class="fas fa-trash"></i></a>';
                                    echo '</div>';
                                    
                                    // Delete confirmation modal
                                    echo '<div class="modal" id="deleteModal' . $product['plant_id'] . '" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">';
                                    echo '<div class="modal-dialog">';
                                    echo '<div class="modal-content">';
                                    echo '<div class="modal-header">';
                                    echo '<h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>';
                                    echo '<button type="button" class="btn-close" data-dismiss="modal" aria-label="Close">&times;</button>';
                                    echo '</div>';
                                    echo '<div class="modal-body">';
                                    echo '<p>Are you sure you want to delete the product: <strong>' . htmlspecialchars($product['name']) . '</strong>?</p>';
                                    echo '<p class="text-danger">This action cannot be undone.</p>';
                                    echo '</div>';
                                    echo '<div class="modal-footer">';
                                    echo '<button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>';
                                    echo '<a href="admin_products.php?delete=' . $product['plant_id'] . '" class="btn btn-danger">Delete</a>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                    
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="8" class="text-center">No products found.</td></tr>';
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
                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
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