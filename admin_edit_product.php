<?php
session_start();
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = 'Product updated successfully!';
}

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_products.php');
    exit();
}

$product_id = $_GET['id'];

// Create product images directory if it doesn't exist
$upload_dir = 'contecnt/img/prodcuts/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Initialize variables
$success_message = $error_message = '';
$product_name = $description = $price = $stock = $category_id = $img_url = '';
$status = 1;
$additional_categories = [];

// Fetch product data
$sql_product = "SELECT * FROM plants WHERE plant_id = ?";
$stmt = mysqli_prepare($conn, $sql_product);
mysqli_stmt_bind_param($stmt, 'i', $product_id);
mysqli_stmt_execute($stmt);
$result_product = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result_product) === 0) {
    header('Location: admin_products.php');
    exit();
}

$product = mysqli_fetch_assoc($result_product);

// Set form fields with product data
$product_name = $product['name'];
$description = $product['description'];
$price = $product['price'];
$stock = $product['stock'];
$img_url = $product['img_url'];
$status = $product['status'];

// Fetch product images
$product_images = array();
$sql_images = "SELECT * FROM plant_images WHERE plant_id = ? ORDER BY is_main DESC, image_id ASC";
$stmt_images = mysqli_prepare($conn, $sql_images);
mysqli_stmt_bind_param($stmt_images, 'i', $product_id);
mysqli_stmt_execute($stmt_images);
$result_images = mysqli_stmt_get_result($stmt_images);

while ($image = mysqli_fetch_assoc($result_images)) {
    $product_images[] = $image;
}

// Fetch product categories
$sql_product_categories = "SELECT category_id FROM plant_category WHERE plant_id = ?";
$stmt_categories = mysqli_prepare($conn, $sql_product_categories);
mysqli_stmt_bind_param($stmt_categories, 'i', $product_id);
mysqli_stmt_execute($stmt_categories);
$result_product_categories = mysqli_stmt_get_result($stmt_categories);

$product_categories = [];
while ($row = mysqli_fetch_assoc($result_product_categories)) {
    $product_categories[] = $row['category_id'];
}

if (!empty($product_categories)) {
    $category_id = $product_categories[0]; // Set primary category
    $additional_categories = array_slice($product_categories, 1); // Set additional categories
}

// Fetch all categories for dropdown
$sql_categories = "SELECT * FROM categories ORDER BY name";
$result_categories = mysqli_query($conn, $sql_categories);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    // Get form data
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $stock = mysqli_real_escape_string($conn, $_POST['stock']);
    $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
    $status = isset($_POST['status']) ? 1 : 0;

    // Validate form data
    if (empty($product_name) || empty($description) || empty($price) || $stock === '' || empty($category_id)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        // Handle image upload if a new main image is selected
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $file_type = $_FILES['product_image']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                $error_message = 'Only JPG, JPEG, and PNG files are allowed.';
            } else {
                $file_name = time() . '_main_' . $_FILES['product_image']['name'];
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                    // Update main image in plants table
                    $img_url = $target_file;
                    
                    // Debug info
                    error_log("Main image uploaded to: " . $target_file);
                    
                    $sql_update_img = "UPDATE plants SET img_url = ? WHERE plant_id = ?";
                    $stmt_update_img = mysqli_prepare($conn, $sql_update_img);
                    
                    if ($stmt_update_img) {
                        mysqli_stmt_bind_param($stmt_update_img, 'si', $img_url, $product_id);
                        mysqli_stmt_execute($stmt_update_img);
                    
                        // First, set all existing images as not main
                        $sql_reset_main = "UPDATE plant_images SET is_main = 0 WHERE plant_id = ?";
                        $stmt_reset_main = mysqli_prepare($conn, $sql_reset_main);
                        
                        if ($stmt_reset_main) {
                            mysqli_stmt_bind_param($stmt_reset_main, 'i', $product_id);
                            mysqli_stmt_execute($stmt_reset_main);
                        }
                        
                        // Check if a main image already exists
                        $sql_check_main = "SELECT image_id FROM plant_images WHERE plant_id = ? AND is_main = 1";
                        $stmt_check_main = mysqli_prepare($conn, $sql_check_main);
                        
                        if ($stmt_check_main) {
                            mysqli_stmt_bind_param($stmt_check_main, 'i', $product_id);
                            mysqli_stmt_execute($stmt_check_main);
                            $result_check_main = mysqli_stmt_get_result($stmt_check_main);
                            
                            if (mysqli_num_rows($result_check_main) > 0) {
                                // Update existing main image
                                $main_image = mysqli_fetch_assoc($result_check_main);
                                $sql_update_main = "UPDATE plant_images SET image_url = ?, is_main = 1 WHERE image_id = ?";
                                $stmt_update_main = mysqli_prepare($conn, $sql_update_main);
                                
                                if ($stmt_update_main) {
                                    mysqli_stmt_bind_param($stmt_update_main, 'si', $img_url, $main_image['image_id']);
                                    mysqli_stmt_execute($stmt_update_main);
                                }
                            } else {
                                // Insert new main image
                                $is_main = 1;
                                $sql_insert_main = "INSERT INTO plant_images (plant_id, image_url, is_main) VALUES (?, ?, ?)";
                                $stmt_insert_main = mysqli_prepare($conn, $sql_insert_main);
                                
                                if ($stmt_insert_main) {
                                    mysqli_stmt_bind_param($stmt_insert_main, 'isi', $product_id, $img_url, $is_main);
                                    mysqli_stmt_execute($stmt_insert_main);
                                }
                            }
                        }
                    }
                } else {
                    $error_message = 'Failed to upload main image. Please try again.';
                }
            }
        }
        
        // Process removed images
        if (isset($_POST['remove_images']) && !empty($_POST['remove_images'])) {
            $removed_ids = explode(',', $_POST['remove_images']);
            foreach ($removed_ids as $image_id) {
                if (is_numeric($image_id)) {
                    // Get image path before deleting
                    $sql_get_image = "SELECT image_url FROM plant_images WHERE image_id = ?";
                    $stmt_get_image = mysqli_prepare($conn, $sql_get_image);
                    mysqli_stmt_bind_param($stmt_get_image, 'i', $image_id);
                    mysqli_stmt_execute($stmt_get_image);
                    $result_get_image = mysqli_stmt_get_result($stmt_get_image);
                    
                    if ($image_data = mysqli_fetch_assoc($result_get_image)) {
                        // Delete file from server (optional)
                        if (file_exists($image_data['image_url'])) {
                            unlink($image_data['image_url']);
                        }
                        
                        // Delete from database
                        $sql_delete_image = "DELETE FROM plant_images WHERE image_id = ?";
                        $stmt_delete_image = mysqli_prepare($conn, $sql_delete_image);
                        mysqli_stmt_bind_param($stmt_delete_image, 'i', $image_id);
                        mysqli_stmt_execute($stmt_delete_image);
                    }
                }
            }
        }
        
        // If no errors, update product in database
        if (empty($error_message)) {
            $sql_update = "UPDATE plants SET name = ?, description = ?, price = ?, stock = ?, status = ? 
                      WHERE plant_id = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            
            if (!$stmt_update) {
                $error_message = "Error preparing statement: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($stmt_update, 'ssdsii', $product_name, $description, $price, $stock, $status, $product_id);
                
                if (mysqli_stmt_execute($stmt_update)) {
                    // Handle additional new images first
                    if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
                        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                        $upload_dir = 'contecnt/img/prodcuts/';
                        
                        // Ensure upload directory exists
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        
                        // Get current image count
                        $sql_count = "SELECT COUNT(*) as count FROM plant_images WHERE plant_id = ?";
                        $stmt_count = mysqli_prepare($conn, $sql_count);
                        $current_image_count = 0;
                        
                        if ($stmt_count) {
                            mysqli_stmt_bind_param($stmt_count, 'i', $product_id);
                            mysqli_stmt_execute($stmt_count);
                            $result_count = mysqli_stmt_get_result($stmt_count);
                            if ($row = mysqli_fetch_assoc($result_count)) {
                                $current_image_count = $row['count'];
                            }
                        }
                        
                        // Calculate how many more images we can add
                        $max_total_images = 4; // Maximum total images including main
                        $max_additional = $max_total_images - $current_image_count;
                        $added_count = 0;
                        
                        if ($max_additional > 0) {
                            for ($i = 0; $i < count($_FILES['additional_images']['name']) && $added_count < $max_additional; $i++) {
                                if ($_FILES['additional_images']['error'][$i] === 0) {
                                    $file_type = $_FILES['additional_images']['type'][$i];
                                    
                                    if (in_array($file_type, $allowed_types)) {
                                        $file_name = time() . '_add_' . $i . '_' . $_FILES['additional_images']['name'][$i];
                                        $target_file = $upload_dir . $file_name;
                                        
                                        if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], $target_file)) {
                                            // Debug info
                                            error_log("Additional image uploaded to: " . $target_file);
                                            
                                            // Insert into plant_images table (not main image)
                                            $is_main = 0;
                                            $sql_insert_image = "INSERT INTO plant_images (plant_id, image_url, is_main) VALUES (?, ?, ?)";
                                            $stmt_insert_image = mysqli_prepare($conn, $sql_insert_image);
                                            
                                            if ($stmt_insert_image) {
                                                mysqli_stmt_bind_param($stmt_insert_image, 'isi', $product_id, $target_file, $is_main);
                                                if (mysqli_stmt_execute($stmt_insert_image)) {
                                                    $added_count++;
                                                    error_log("Successfully added additional image to database");
                                                } else {
                                                    error_log("Failed to insert additional image: " . mysqli_error($conn));
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    // Set main image if requested
                    if (isset($_POST['set_main_image']) && is_numeric($_POST['set_main_image'])) {
                        $new_main_image_id = $_POST['set_main_image'];
                        
                        // Get the image URL
                        $sql_get_image = "SELECT image_url FROM plant_images WHERE image_id = ?";
                        $stmt_get_image = mysqli_prepare($conn, $sql_get_image);
                        if ($stmt_get_image) {
                            mysqli_stmt_bind_param($stmt_get_image, 'i', $new_main_image_id);
                            mysqli_stmt_execute($stmt_get_image);
                            $result_get_image = mysqli_stmt_get_result($stmt_get_image);
                            
                            if ($image_data = mysqli_fetch_assoc($result_get_image)) {
                                // Update plants table with new main image
                                $sql_update_main_img = "UPDATE plants SET img_url = ? WHERE plant_id = ?";
                                $stmt_update_main_img = mysqli_prepare($conn, $sql_update_main_img);
                                if ($stmt_update_main_img) {
                                    mysqli_stmt_bind_param($stmt_update_main_img, 'si', $image_data['image_url'], $product_id);
                                    mysqli_stmt_execute($stmt_update_main_img);
                                    
                                    // Reset all images to not main
                                    $sql_reset_main = "UPDATE plant_images SET is_main = 0 WHERE plant_id = ?";
                                    $stmt_reset_main = mysqli_prepare($conn, $sql_reset_main);
                                    if ($stmt_reset_main) {
                                        mysqli_stmt_bind_param($stmt_reset_main, 'i', $product_id);
                                        mysqli_stmt_execute($stmt_reset_main);
                                        
                                        // Set selected image as main
                                        $sql_set_main = "UPDATE plant_images SET is_main = 1 WHERE image_id = ?";
                                        $stmt_set_main = mysqli_prepare($conn, $sql_set_main);
                                        if ($stmt_set_main) {
                                            mysqli_stmt_bind_param($stmt_set_main, 'i', $new_main_image_id);
                                            mysqli_stmt_execute($stmt_set_main);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    // Now handle categories
                    // Delete existing product categories
                    $sql_delete_categories = "DELETE FROM plant_category WHERE plant_id = ?";
                    $stmt_delete = mysqli_prepare($conn, $sql_delete_categories);
                    
                    if ($stmt_delete) {
                        mysqli_stmt_bind_param($stmt_delete, 'i', $product_id);
                        mysqli_stmt_execute($stmt_delete);
                        
                        // Insert primary category
                        $sql_cat = "INSERT INTO plant_category (plant_id, category_id) VALUES (?, ?)";
                        $stmt_cat = mysqli_prepare($conn, $sql_cat);
                        if ($stmt_cat) {
                            mysqli_stmt_bind_param($stmt_cat, 'ii', $product_id, $category_id);
                            mysqli_stmt_execute($stmt_cat);
                            
                            // Insert additional categories if any
                            if (isset($_POST['additional_categories']) && is_array($_POST['additional_categories'])) {
                                foreach ($_POST['additional_categories'] as $cat_id) {
                                    if ($cat_id != $category_id) { // Skip if same as primary category
                                        $sql_cat = "INSERT INTO plant_category (plant_id, category_id) VALUES (?, ?)";
                                        $stmt_cat = mysqli_prepare($conn, $sql_cat);
                                        if ($stmt_cat) {
                                            mysqli_stmt_bind_param($stmt_cat, 'ii', $product_id, $cat_id);
                                            mysqli_stmt_execute($stmt_cat);
                                        }
                                    }
                                }
                            }
                            
                            $success_message = 'Product updated successfully!';
                            
                            // Redirect to refresh the page and clear POST data
                            header("Location: admin_edit_product.php?id=" . $product_id . "&success=1");
                            exit();
                        } else {
                            $error_message = 'Error preparing category statement: ' . mysqli_error($conn);
                        }
                    } else {
                        $error_message = 'Error preparing delete categories statement: ' . mysqli_error($conn);
                    }
                } else {
                    $error_message = 'Error updating product: ' . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Kiyara Plants Admin</title>
    
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
                <span class="navbar-brand">Edit Product: <?php echo htmlspecialchars($product_name); ?></span>
                <a href="admin_products.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
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
        
        <!-- Edit Product Form -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Product Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-8">
                            <!-- Basic Info -->
                            <div class="mb-3">
                                <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Price (Rs.) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($price); ?>" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label for="stock" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?php echo htmlspecialchars($stock); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Categories -->
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Primary Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php
                                    if ($result_categories && mysqli_num_rows($result_categories) > 0) {
                                        while ($category = mysqli_fetch_assoc($result_categories)) {
                                            $selected = ($category_id == $category['category_id']) ? 'selected' : '';
                                            echo '<option value="' . $category['category_id'] . '" ' . $selected . '>' . htmlspecialchars($category['name']) . '</option>';
                                        }
                                        // Reset result pointer for the additional categories
                                        mysqli_data_seek($result_categories, 0);
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Additional Categories (Optional)</label>
                                <div class="checkbox-group">
                                    <?php
                                    if ($result_categories && mysqli_num_rows($result_categories) > 0) {
                                        while ($category = mysqli_fetch_assoc($result_categories)) {
                                            $checked = in_array($category['category_id'], $additional_categories) ? 'checked' : '';
                                            echo '<div class="form-check">';
                                            echo '<input class="form-check-input" type="checkbox" name="additional_categories[]" value="' . $category['category_id'] . '" id="cat' . $category['category_id'] . '" ' . $checked . '>';
                                            echo '<label class="form-check-label" for="cat' . $category['category_id'] . '">' . htmlspecialchars($category['name']) . '</label>';
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-4">
                            <!-- Main Product Image -->
                            <div class="mb-3">
                                <label for="product_image" class="form-label">Main Product Image</label>
                                <input type="file" class="form-control" id="product_image" name="product_image" accept="image/jpeg, image/png, image/jpg" autocomplete="off">
                                <div class="mt-3 text-center">
                                    <div id="image-preview" class="product-image-preview">
                                        <?php if (!empty($img_url)): ?>
                                        <img src="<?php echo htmlspecialchars($img_url); ?>" class="img-fluid" alt="Current Product Image" style="max-height: 200px; object-fit: contain;">
                                        <p class="mt-2">Current Main Image</p>
                                        <?php else: ?>
                                        <i class="fas fa-leaf fa-3x"></i>
                                        <p>No main image available</p>
                                        <?php endif; ?>
                                    </div>
                                    <div id="image-info" class="mt-2" style="display: none;">
                                        <p id="image-size" class="mb-1 text-muted" style="font-size: 13px;"></p>
                                        <button type="button" id="remove-image" class="btn btn-sm btn-danger mt-1">
                                            <i class="fas fa-trash"></i> Remove Image
                                        </button>
                                    </div>
                                    <small class="text-muted mt-2 d-block">Leave empty to keep current main image.</small>
                                </div>
                            </div>
                            
                            <!-- Additional Product Images -->
                            <div class="mb-3">
                                <label class="form-label">Product Gallery</label>
                                <div class="mb-3">
                                    <div class="row g-2" id="existing-images">
                                        <?php foreach ($product_images as $image): ?>
                                        <div class="col-6 mb-2" data-image-id="<?php echo $image['image_id']; ?>">
                                            <div class="card h-100">
                                                <div class="image-container position-relative" style="height: 120px; overflow: hidden;">
                                                    <img src="<?php echo htmlspecialchars($image['image_url']); ?>" class="card-img-top img-fluid" alt="Product Image" style="object-fit: contain; height: 100%; width: 100%;">
                                                    <?php if ($image['is_main']): ?>
                                                    <span class="badge bg-success position-absolute top-0 start-0 m-1">Main</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-body p-2">
                                                    <div class="btn-group btn-group-sm w-100">
                                                        <?php if (!$image['is_main']): ?>
                                                        <button type="button" class="btn btn-outline-primary set-main-btn" data-image-id="<?php echo $image['image_id']; ?>">
                                                            Set as Main
                                                        </button>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-outline-danger remove-btn" data-image-id="<?php echo $image['image_id']; ?>">
                                                            Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="remove_images" id="remove_images" value="">
                                    <input type="hidden" name="set_main_image" id="set_main_image" value="">
                                </div>
                                
                                <label for="additional_images" class="form-label">Add More Images</label>
                                <input type="file" class="form-control" id="additional_images" name="additional_images[]" accept="image/jpeg, image/png, image/jpg" multiple autocomplete="off">
                                <div class="mt-3">
                                    <div id="additional-images-preview" class="row g-2">
                                        <!-- Additional images previews will appear here -->
                                    </div>
                                </div>
                                <small class="text-muted">Maximum 4 total images (including main). Current: <?php echo count($product_images); ?> image(s).</small>
                            </div>
                            
                            <!-- Status -->
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="status" name="status" <?php echo $status ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status">Active (visible on shop)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-end">
                        <a href="admin_products.php" class="btn btn-outline-secondary me-2">Cancel</a>
                        <button type="submit" name="update_product" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Admin Sidebar JS -->
    <script src="contecnt/js/admin_sidebar.js"></script>
    <!-- Admin Modal JS -->
    <script src="contecnt/js/admin_modal.js"></script>
    
    <script>
    // Image Preview and Management
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('product_image');
        const imagePreview = document.getElementById('image-preview');
        const imageInfo = document.getElementById('image-info');
        const imageSize = document.getElementById('image-size');
        const removeImageBtn = document.getElementById('remove-image');
        const additionalImagesInput = document.getElementById('additional_images');
        const additionalImagesPreview = document.getElementById('additional-images-preview');
        const removeImagesInput = document.getElementById('remove_images');
        const setMainImageInput = document.getElementById('set_main_image');
        
        // Add autocomplete="off" to all form inputs
        document.querySelectorAll('input, textarea, select').forEach(input => {
            input.setAttribute('autocomplete', 'off');
        });
        
        // Main image preview handler
        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.innerHTML = `<img src="${e.target.result}" class="img-fluid" alt="Product Preview" style="max-height: 200px; width: auto; object-fit: contain;">
                                            <p class="mt-2">New Main Image Preview</p>`;
                    
                    // Display file size
                    const fileSizeKB = Math.round(file.size / 1024);
                    const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                    
                    if (fileSizeKB > 1024) {
                        imageSize.textContent = `File size: ${fileSizeMB} MB (${file.name})`;
                    } else {
                        imageSize.textContent = `File size: ${fileSizeKB} KB (${file.name})`;
                    }
                    
                    imageInfo.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            }
        });
        
        // Additional images preview handler
        additionalImagesInput.addEventListener('change', function() {
            additionalImagesPreview.innerHTML = '';
            
            if (this.files && this.files.length > 0) {
                // Calculate how many more images we can add
                const currentImageCount = <?php echo count($product_images); ?>;
                const maxTotalImages = 4;
                const maxToAdd = Math.max(0, maxTotalImages - currentImageCount + document.querySelectorAll('.remove-btn[disabled]').length);
                const fileCount = Math.min(this.files.length, maxToAdd);
                
                if (this.files.length > maxToAdd) {
                    alert(`You can only add ${maxToAdd} more image(s) to reach the maximum of ${maxTotalImages} total images.`);
                }
                
                for (let i = 0; i < fileCount; i++) {
                    const file = this.files[i];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const col = document.createElement('div');
                        col.className = 'col-6 mb-2';
                        
                        const card = document.createElement('div');
                        card.className = 'card h-100';
                        
                        const imgContainer = document.createElement('div');
                        imgContainer.className = 'image-container position-relative';
                        imgContainer.style.height = '120px';
                        imgContainer.style.overflow = 'hidden';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'card-img-top img-fluid';
                        img.style.objectFit = 'contain';
                        img.style.height = '100%';
                        img.style.width = '100%';
                        img.alt = 'New Image';
                        
                        const cardBody = document.createElement('div');
                        cardBody.className = 'card-body p-2';
                        
                        const fileSizeKB = Math.round(file.size / 1024);
                        const sizeLabel = document.createElement('small');
                        sizeLabel.className = 'text-muted';
                        sizeLabel.textContent = `${fileSizeKB} KB`;
                        
                        imgContainer.appendChild(img);
                        card.appendChild(imgContainer);
                        cardBody.appendChild(sizeLabel);
                        card.appendChild(cardBody);
                        col.appendChild(card);
                        additionalImagesPreview.appendChild(col);
                    }
                    
                    reader.readAsDataURL(file);
                }
            }
        });
        
        // Remove main image handler
        removeImageBtn.addEventListener('click', function() {
            imageInput.value = '';
            
            <?php if (!empty($img_url)): ?>
            imagePreview.innerHTML = `<img src="<?php echo htmlspecialchars($img_url); ?>" class="img-fluid" alt="Current Product Image" style="max-height: 200px; object-fit: contain;">
                                    <p class="mt-2">Current Main Image</p>`;
            <?php else: ?>
            imagePreview.innerHTML = `<i class="fas fa-leaf fa-3x"></i><p>No main image available</p>`;
            <?php endif; ?>
            
            imageInfo.style.display = 'none';
        });
        
        // Handle remove image buttons
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const imageId = this.getAttribute('data-image-id');
                const container = this.closest('[data-image-id]');
                
                // Add to remove list
                const currentRemoveList = removeImagesInput.value.split(',').filter(id => id.trim() !== '');
                currentRemoveList.push(imageId);
                removeImagesInput.value = currentRemoveList.join(',');
                
                // Hide from UI
                container.style.opacity = '0.3';
                btn.disabled = true;
                
                // Disable set as main button if exists
                const setMainBtn = container.querySelector('.set-main-btn');
                if (setMainBtn) {
                    setMainBtn.disabled = true;
                }
            });
        });
        
        // Handle set as main image buttons
        document.querySelectorAll('.set-main-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const imageId = this.getAttribute('data-image-id');
                
                // Remove 'Main' badge from all images
                document.querySelectorAll('.badge.bg-success').forEach(badge => {
                    badge.remove();
                });
                
                // Add 'Main' badge to selected image
                const imageContainer = this.closest('[data-image-id]').querySelector('.image-container');
                const badge = document.createElement('span');
                badge.className = 'badge bg-success position-absolute top-0 start-0 m-1';
                badge.textContent = 'Main';
                imageContainer.appendChild(badge);
                
                // Set value in hidden input
                setMainImageInput.value = imageId;
                
                // Disable all set-main buttons
                document.querySelectorAll('.set-main-btn').forEach(b => {
                    b.disabled = true;
                });
                
                // Show message
                alert('This image will be set as the main product image when you save changes.');
            });
        });
        
        // Alert dismissal
        const alertCloseButtons = document.querySelectorAll('.alert .btn-close');
        alertCloseButtons.forEach(button => {
            button.addEventListener('click', function() {
                const alert = this.closest('.alert');
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            });
        });
        
        // Display success message with setTimeout to auto-hide
        <?php if (!empty($success_message)): ?>
        setTimeout(() => {
            const successAlert = document.querySelector('.alert-success');
            if (successAlert) {
                successAlert.style.opacity = '0';
                setTimeout(() => {
                    successAlert.style.display = 'none';
                }, 300);
            }
        }, 3000); // Hide after 3 seconds
        <?php endif; ?>
    });
    </script>

    <!-- Add custom styles for the image preview -->
    <style>
    .product-image-preview {
        border: 1px dashed #ccc;
        padding: 20px;
        border-radius: 5px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 200px;
        background-color: #f9f9f9;
    }

    .product-image-preview i {
        color: #aaa;
        margin-bottom: 10px;
    }

    .product-image-preview img {
        max-height: 200px;
        width: auto;
        object-fit: contain;
    }

    .card-img-top {
        padding: 8px;
        background-color: #f9f9f9;
    }
    </style>
</body>
</html> 