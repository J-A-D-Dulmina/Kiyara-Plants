<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin.php");
    exit();
}

include 'db_connection.php';

$admin_id = $_SESSION["admin_id"];
$success_message = '';
$error_message = '';

// Get admin details
$sql_admin = "SELECT * FROM admintb WHERE admin_id = ?";
$stmt_admin = mysqli_prepare($conn, $sql_admin);
mysqli_stmt_bind_param($stmt_admin, "i", $admin_id);
mysqli_stmt_execute($stmt_admin);
$result_admin = mysqli_stmt_get_result($stmt_admin);
$admin = mysqli_fetch_assoc($result_admin);

// Process profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $postal_code = trim($_POST['postal_code']);
    $age = trim($_POST['age']);
    
    // Validate required fields
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required fields.";
    } else {
        // Check if email already exists with different admin ID
        $sql_check_email = "SELECT admin_id FROM admintb WHERE email = ? AND admin_id != ?";
        $stmt_check_email = mysqli_prepare($conn, $sql_check_email);
        mysqli_stmt_bind_param($stmt_check_email, "si", $email, $admin_id);
        mysqli_stmt_execute($stmt_check_email);
        $result_check_email = mysqli_stmt_get_result($stmt_check_email);
        
        if (mysqli_num_rows($result_check_email) > 0) {
            $error_message = "Email already exists. Please use a different email.";
        } else {
            // Handle profile image upload if provided
            $profile_image = $admin['user_image']; // Default to existing image
            
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = basename($_FILES['profile_image']['name']);
                $target_file = $upload_dir . $file_name;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                    $profile_image = $target_file;
                } else {
                    $error_message = "Error uploading profile image. Your profile details will still be updated.";
                }
            }
            
            // Update admin profile
            $sql_update = "UPDATE admintb SET name = ?, email = ?, phone = ?, address = ?, postal_code = ?, age = ?, user_image = ? WHERE admin_id = ?";
            $stmt_update = mysqli_prepare($conn, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "sssssssi", $name, $email, $phone, $address, $postal_code, $age, $profile_image, $admin_id);
            
            if (mysqli_stmt_execute($stmt_update)) {
                $success_message = "Profile updated successfully!";
                
                // Update session data
                $_SESSION["admin_name"] = $name;
                
                // Refresh admin data
                mysqli_stmt_execute($stmt_admin);
                $result_admin = mysqli_stmt_get_result($stmt_admin);
                $admin = mysqli_fetch_assoc($result_admin);
            } else {
                $error_message = "Error updating profile. Please try again.";
            }
        }
    }
}

// Process password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords match
    if ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } else {
        // Verify current password
        $sql_check_password = "SELECT admin_id FROM admintb WHERE admin_id = ? AND password = ?";
        $stmt_check_password = mysqli_prepare($conn, $sql_check_password);
        mysqli_stmt_bind_param($stmt_check_password, "is", $admin_id, $current_password);
        mysqli_stmt_execute($stmt_check_password);
        $result_check_password = mysqli_stmt_get_result($stmt_check_password);
        
        if (mysqli_num_rows($result_check_password) === 0) {
            $error_message = "Current password is incorrect.";
        } else {
            // Update password
            $sql_update_password = "UPDATE admintb SET password = ? WHERE admin_id = ?";
            $stmt_update_password = mysqli_prepare($conn, $sql_update_password);
            mysqli_stmt_bind_param($stmt_update_password, "si", $new_password, $admin_id);
            
            if (mysqli_stmt_execute($stmt_update_password)) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Error changing password. Please try again.";
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
    <title>Admin Profile - Kiyara Plants</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Admin Styles -->
    <link rel="stylesheet" href="contecnt/css/admin_style.css">
    <link rel="stylesheet" href="contecnt/css/admin_profile.css">
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
            <li><a href="admin_profile.php" class="active"><i class="fas fa-user-cog"></i> Profile</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <!-- Content -->
    <div class="content">
        <!-- Navbar -->
        <nav class="navbar navbar-light mb-4">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h1">Admin Profile</span>
            </div>
        </nav>
        
        <!-- Alerts -->
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Profile Card -->
        <div class="card">
            <div class="profile-header">
                <?php if (!empty($admin['user_image']) && $admin['user_image'] != 'Add a Photo'): ?>
                <img src="<?php echo $admin['user_image']; ?>" alt="Profile" class="profile-avatar">
                <?php else: ?>
                <div class="profile-avatar-placeholder">
                    <i class="fas fa-user"></i>
                </div>
                <?php endif; ?>
                
                <h1 class="profile-name"><?php echo htmlspecialchars($admin['name']); ?></h1>
                <p class="profile-info"><?php echo htmlspecialchars($admin['email']); ?></p>
            </div>
            
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                        <i class="fas fa-user me-2"></i>Profile Details
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">
                        <i class="fas fa-lock me-2"></i>Change Password
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="myTabContent">
                <!-- Profile Tab -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($admin['phone'] == 'Add a Number' ? '' : $admin['phone']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="age" class="form-label">Age</label>
                                    <input type="text" class="form-control" id="age" name="age" value="<?php echo htmlspecialchars($admin['age'] == 'Add age' ? '' : $admin['age']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($admin['address'] == 'Add a address' ? '' : $admin['address']); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="postal_code" class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($admin['postal_code'] == 'Add a Postal Code' ? '' : $admin['postal_code']); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="profile_image" class="form-label">Profile Image</label>
                                    <div class="custom-file">
                                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" onchange="previewImage(this)">
                                    </div>
                                    <img id="preview" class="preview-image" src="#" alt="Image Preview">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" name="update_profile" class="btn btn-success">Update Profile</button>
                        </div>
                    </form>
                </div>
                
                <!-- Password Change Tab -->
                <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" name="change_password" class="btn btn-success">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function previewImage(input) {
            var preview = document.getElementById('preview');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = '#';
                preview.style.display = 'none';
            }
        }
    </script>
</body>
</html> 