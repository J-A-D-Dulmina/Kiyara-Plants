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

// Handle category deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $category_id = $_GET['delete'];
    
    // First check if there are products in this category
    $sql_check = "SELECT COUNT(*) as count FROM plant_category WHERE category_id = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "i", $category_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $row_check = mysqli_fetch_assoc($result_check);
    
    if ($row_check['count'] > 0) {
        $error_message = "Cannot delete category. It is associated with " . $row_check['count'] . " product(s).";
    } else {
        // Delete the category
        $sql_delete = "DELETE FROM categories WHERE category_id = ?";
        $stmt_delete = mysqli_prepare($conn, $sql_delete);
        mysqli_stmt_bind_param($stmt_delete, "i", $category_id);
        
        if (mysqli_stmt_execute($stmt_delete)) {
            $success_message = "Category deleted successfully!";
        } else {
            $error_message = "Error deleting category. Please try again.";
        }
    }
}

// Handle add category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    
    if (empty($category_name)) {
        $error_message = "Category name cannot be empty.";
    } else {
        // Check if category already exists
        $sql_check = "SELECT COUNT(*) as count FROM categories WHERE name = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "s", $category_name);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $row_check = mysqli_fetch_assoc($result_check);
        
        if ($row_check['count'] > 0) {
            $error_message = "A category with this name already exists.";
        } else {
            // Insert the category
            $sql_insert = "INSERT INTO categories (name) VALUES (?)";
            $stmt_insert = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "s", $category_name);
            
            if (mysqli_stmt_execute($stmt_insert)) {
                $success_message = "Category added successfully!";
                // Clear form data
                $_POST = array();
            } else {
                $error_message = "Error adding category. Please try again.";
            }
        }
    }
}

// Handle edit category
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_category'])) {
    $category_id = $_POST['category_id'];
    $category_name = trim($_POST['edit_category_name']);
    
    if (empty($category_name)) {
        $error_message = "Category name cannot be empty.";
    } else {
        // Check if category already exists with the same name but different ID
        $sql_check = "SELECT COUNT(*) as count FROM categories WHERE name = ? AND category_id != ?";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "si", $category_name, $category_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $row_check = mysqli_fetch_assoc($result_check);
        
        if ($row_check['count'] > 0) {
            $error_message = "A category with this name already exists.";
        } else {
            // Update the category
            $sql_update = "UPDATE categories SET name = ? WHERE category_id = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "si", $category_name, $category_id);
            
            if (mysqli_stmt_execute($stmt_update)) {
                $success_message = "Category updated successfully!";
            } else {
                $error_message = "Error updating category. Please try again.";
            }
        }
    }
}

// Get all categories
$sql_categories = "SELECT c.category_id, c.name, COUNT(pc.plant_id) as product_count 
                  FROM categories c 
                  LEFT JOIN plant_category pc ON c.category_id = pc.category_id 
                  GROUP BY c.category_id 
                  ORDER BY c.name";
$result_categories = mysqli_query($conn, $sql_categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Kiyara Plants Admin</title>
    
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
                <span class="navbar-brand">Manage Categories</span>
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
        
        <div class="row">
            <!-- Add Category Card -->
            <div class="col-4 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Add New Category</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group mb-3">
                                <label for="category_name" class="form-label">Category Name</label>
                                <input type="text" class="form-control" id="category_name" name="category_name" required value="<?php echo isset($_POST['category_name']) ? htmlspecialchars($_POST['category_name']) : ''; ?>">
                            </div>
                            <button type="submit" name="add_category" class="btn btn-success w-100">Add Category</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Categories Table Card -->
            <div class="col-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">All Categories</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category Name</th>
                                        <th>Products</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result_categories && mysqli_num_rows($result_categories) > 0) {
                                        while ($category = mysqli_fetch_assoc($result_categories)) {
                                            echo '<tr>';
                                            echo '<td>#' . $category['category_id'] . '</td>';
                                            echo '<td>' . htmlspecialchars($category['name']) . '</td>';
                                            echo '<td>' . $category['product_count'] . '</td>';
                                            echo '<td>';
                                            echo '<div class="btn-group">';
                                            echo '<button type="button" class="btn btn-primary me-1" data-toggle="modal" data-target="#editModal' . $category['category_id'] . '"><i class="fas fa-edit"></i></button>';
                                            
                                            if ($category['product_count'] == 0) {
                                                echo '<a href="#" data-toggle="modal" data-target="#deleteModal' . $category['category_id'] . '" class="btn btn-danger"><i class="fas fa-trash"></i></a>';
                                            } else {
                                                echo '<button type="button" class="btn btn-secondary" disabled title="Cannot delete - category has products"><i class="fas fa-trash"></i></button>';
                                            }
                                            
                                            echo '</div>';
                                            
                                            // Edit modal
                                            echo '<div class="modal" id="editModal' . $category['category_id'] . '" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">';
                                            echo '<div class="modal-dialog">';
                                            echo '<div class="modal-content">';
                                            echo '<div class="modal-header">';
                                            echo '<h5 class="modal-title" id="editModalLabel">Edit Category</h5>';
                                            echo '<button type="button" class="btn-close" data-dismiss="modal" aria-label="Close">&times;</button>';
                                            echo '</div>';
                                            echo '<div class="modal-body">';
                                            echo '<form method="POST">';
                                            echo '<input type="hidden" name="category_id" value="' . $category['category_id'] . '">';
                                            echo '<div class="form-group mb-3">';
                                            echo '<label for="edit_category_name' . $category['category_id'] . '" class="form-label">Category Name</label>';
                                            echo '<input type="text" class="form-control" id="edit_category_name' . $category['category_id'] . '" name="edit_category_name" value="' . htmlspecialchars($category['name']) . '" required>';
                                            echo '</div>';
                                            echo '<button type="submit" name="edit_category" class="btn btn-success">Update Category</button>';
                                            echo '</form>';
                                            echo '</div>';
                                            echo '</div>';
                                            echo '</div>';
                                            echo '</div>';
                                            
                                            // Delete confirmation modal
                                            if ($category['product_count'] == 0) {
                                                echo '<div class="modal" id="deleteModal' . $category['category_id'] . '" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">';
                                                echo '<div class="modal-dialog">';
                                                echo '<div class="modal-content">';
                                                echo '<div class="modal-header">';
                                                echo '<h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>';
                                                echo '<button type="button" class="btn-close" data-dismiss="modal" aria-label="Close">&times;</button>';
                                                echo '</div>';
                                                echo '<div class="modal-body">';
                                                echo '<p>Are you sure you want to delete the category: <strong>' . htmlspecialchars($category['name']) . '</strong>?</p>';
                                                echo '</div>';
                                                echo '<div class="modal-footer">';
                                                echo '<button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>';
                                                echo '<a href="admin_categories.php?delete=' . $category['category_id'] . '" class="btn btn-danger">Delete</a>';
                                                echo '</div>';
                                                echo '</div>';
                                                echo '</div>';
                                                echo '</div>';
                                            }
                                            
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center">No categories found.</td></tr>';
                                    }
                                    ?>
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