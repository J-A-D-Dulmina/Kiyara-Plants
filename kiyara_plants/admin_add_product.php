<?php
session_start();
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Create product images directory if it doesn't exist
$upload_dir = 'contecnt/img/products/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Initialize variables
$success_message = $error_message = '';
$product_name = $description = $price = $stock = $category_id = '';

// Fetch categories for dropdown
$sql_categories = "SELECT * FROM categories ORDER BY name";
$result_categories = mysqli_query($conn, $sql_categories);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
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
        // Handle main image upload
        $img_url = '';
        
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $file_type = $_FILES['product_image']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                $error_message = 'Only JPG, JPEG, and PNG files are allowed.';
            } else {
                $file_name = time() . '_main_' . $_FILES['product_image']['name'];
                
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['product_image']['tmp_name'], $target_file)) {
                    $img_url = $target_file;
                } else {
                    $error_message = 'Failed to upload main image. Please try again.';
                }
            }
        } else {
            $error_message = 'Please select a main image for the product.';
        }
        
        // If no errors, insert product into database
        if (empty($error_message)) {
            $sql_insert = "INSERT INTO plants (name, description, price, stock, img_url, status, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW())";
                          
            $stmt = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param($stmt, 'ssdssi', $product_name, $description, $price, $stock, $img_url, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                $product_id = mysqli_insert_id($conn);
                
                // Insert product categories
                if (isset($_POST['additional_categories']) && is_array($_POST['additional_categories'])) {
                    // Insert primary category first
                    $sql_cat = "INSERT INTO plant_category (plant_id, category_id) VALUES (?, ?)";
                    $stmt_cat = mysqli_prepare($conn, $sql_cat);
                    mysqli_stmt_bind_param($stmt_cat, 'ii', $product_id, $category_id);
                    mysqli_stmt_execute($stmt_cat);
                    
                    // Insert additional categories
                    foreach ($_POST['additional_categories'] as $cat_id) {
                        if ($cat_id != $category_id) { // Skip if same as primary category
                            $sql_cat = "INSERT INTO plant_category (plant_id, category_id) VALUES (?, ?)";
                            $stmt_cat = mysqli_prepare($conn, $sql_cat);
                            mysqli_stmt_bind_param($stmt_cat, 'ii', $product_id, $cat_id);
                            mysqli_stmt_execute($stmt_cat);
                        }
                    }
                } else {
                    // Insert just the primary category
                    $sql_cat = "INSERT INTO plant_category (plant_id, category_id) VALUES (?, ?)";
                    $stmt_cat = mysqli_prepare($conn, $sql_cat);
                    mysqli_stmt_bind_param($stmt_cat, 'ii', $product_id, $category_id);
                    mysqli_stmt_execute($stmt_cat);
                }
                
                // Handle additional product images
                if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
                    $max_additional_images = 3; // Limit to 3 additional images
                    $count = 0;
                    
                    for ($i = 0; $i < count($_FILES['additional_images']['name']) && $count < $max_additional_images; $i++) {
                        if ($_FILES['additional_images']['error'][$i] === 0) {
                            $file_type = $_FILES['additional_images']['type'][$i];
                            
                            if (in_array($file_type, $allowed_types)) {
                                $file_name = time() . '_add_' . $i . '_' . $_FILES['additional_images']['name'][$i];
                                $target_file = $upload_dir . $file_name;
                                
                                if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], $target_file)) {
                                    // Insert into plant_images table
                                    $is_main = 0; // Additional images are not main by default
                                    $sql_image = "INSERT INTO plant_images (plant_id, image_url, is_main) VALUES (?, ?, ?)";
                                    $stmt_image = mysqli_prepare($conn, $sql_image);
                                    mysqli_stmt_bind_param($stmt_image, 'isi', $product_id, $target_file, $is_main);
                                    mysqli_stmt_execute($stmt_image);
                                    $count++;
                                }
                            }
                        }
                    }
                }
                
                // Also save the main image in plant_images with is_main = 1
                $is_main = 1;
                $sql_main_image = "INSERT INTO plant_images (plant_id, image_url, is_main) VALUES (?, ?, ?)";
                $stmt_main_image = mysqli_prepare($conn, $sql_main_image);
                mysqli_stmt_bind_param($stmt_main_image, 'isi', $product_id, $img_url, $is_main);
                mysqli_stmt_execute($stmt_main_image);
                
                $success_message = 'Product added successfully!';
                // Reset form data
                $product_name = $description = $price = $stock = $category_id = '';
                
                // Redirect to the product list page
                header("Location: admin_products.php?success=1");
                exit();
            } else {
                $error_message = 'Error adding product: ' . mysqli_error($conn);
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
    <title>Add Product - Kiyara Plants Admin</title>
    
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
                <span class="navbar-brand">Add New Product</span>
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
        
        <!-- Add Product Form -->
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
                                            echo '<div class="form-check">';
                                            echo '<input class="form-check-input" type="checkbox" name="additional_categories[]" value="' . $category['category_id'] . '" id="cat' . $category['category_id'] . '">';
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
                                <label for="product_image" class="form-label">Main Product Image <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="product_image" name="product_image" accept="image/jpeg, image/png, image/jpg" required autocomplete="off">
                                <div class="mt-3 text-center">
                                    <div id="image-preview" class="product-image-preview">
                                        <i class="fas fa-leaf fa-3x"></i>
                                        <p>Main image preview will appear here</p>
                                    </div>
                                    <div id="image-info" class="mt-2" style="display: none;">
                                        <p id="image-size" class="mb-1 text-muted" style="font-size: 13px;"></p>
                                        <button type="button" id="remove-image" class="btn btn-sm btn-danger mt-1">
                                            <i class="fas fa-trash"></i> Remove Image
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Additional Product Images -->
                            <div class="mb-3">
                                <label for="additional_images" class="form-label">Additional Images (Optional)</label>
                                <input type="file" class="form-control" id="additional_images" name="additional_images[]" accept="image/jpeg, image/png, image/jpg" multiple autocomplete="off">
                                <div class="mt-3">
                                    <div id="additional-images-preview" class="row g-2">
                                        <!-- Additional images previews will appear here -->
                                    </div>
                                </div>
                                <small class="text-muted">You can select up to 3 additional images (maximum 4 total including main image)</small>
                            </div>
                            
                            <!-- Status -->
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="status" name="status" checked>
                                    <label class="form-check-label" for="status">Active (visible on shop)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-end">
                        <a href="admin_products.php" class="btn btn-outline-secondary me-2">Cancel</a>
                        <button type="submit" name="add_product" class="btn btn-success">
                            <i class="fas fa-plus-circle"></i> Add Product
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
    // Image Preview
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('product_image');
        const imagePreview = document.getElementById('image-preview');
        const imageInfo = document.getElementById('image-info');
        const imageSize = document.getElementById('image-size');
        const removeImageBtn = document.getElementById('remove-image');
        const additionalImagesInput = document.getElementById('additional_images');
        const additionalImagesPreview = document.getElementById('additional-images-preview');
        
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
                    imagePreview.innerHTML = `<img src="${e.target.result}" class="img-fluid" alt="Product Preview" style="max-height: 200px; width: auto; object-fit: contain;">`;
                    
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
                // Limit to maximum 3 additional images
                const maxAdditionalImages = 3;
                const fileCount = Math.min(this.files.length, maxAdditionalImages);
                
                if (this.files.length > maxAdditionalImages) {
                    alert('You can upload a maximum of 3 additional images. Only the first 3 selected images will be processed.');
                }
                
                for (let i = 0; i < fileCount; i++) {
                    const file = this.files[i];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const col = document.createElement('div');
                        col.className = 'col-6 mb-2';
                        
                        const imgContainer = document.createElement('div');
                        imgContainer.className = 'additional-image-container';
                        imgContainer.style.position = 'relative';
                        imgContainer.style.height = '120px';
                        imgContainer.style.border = '1px dashed #ccc';
                        imgContainer.style.borderRadius = '5px';
                        imgContainer.style.overflow = 'hidden';
                        imgContainer.style.display = 'flex';
                        imgContainer.style.alignItems = 'center';
                        imgContainer.style.justifyContent = 'center';
                        imgContainer.style.backgroundColor = '#f9f9f9';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'img-fluid';
                        img.style.maxHeight = '100%';
                        img.style.maxWidth = '100%';
                        img.style.objectFit = 'contain';
                        
                        imgContainer.appendChild(img);
                        col.appendChild(imgContainer);
                        additionalImagesPreview.appendChild(col);
                        
                        const fileSizeKB = Math.round(file.size / 1024);
                        const sizeLabel = document.createElement('small');
                        sizeLabel.className = 'text-muted';
                        sizeLabel.style.display = 'block';
                        sizeLabel.style.textAlign = 'center';
                        sizeLabel.style.marginTop = '5px';
                        sizeLabel.textContent = `${fileSizeKB} KB`;
                        col.appendChild(sizeLabel);
                    }
                    
                    reader.readAsDataURL(file);
                }
            }
        });
        
        // Remove image handler
        removeImageBtn.addEventListener('click', function() {
            imageInput.value = '';
            imagePreview.innerHTML = `<i class="fas fa-leaf fa-3x"></i><p>Main image preview will appear here</p>`;
            imageInfo.style.display = 'none';
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
    </style>
</body>
</html> 