<?php
session_start();
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to add items to your wishlist'
    ]);
    exit();
}

// Check if request is POST and has plant_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plant_id'])) {
    $user_id = $_SESSION['user_id'];
    $plant_id = (int)$_POST['plant_id'];
    
    // Validate plant_id
    if ($plant_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid product'
        ]);
        exit();
    }
    
    // First check if the plant exists
    $sql_check_plant = "SELECT plant_id, name FROM plants WHERE plant_id = ? AND status = 1";
    $stmt_check_plant = mysqli_prepare($conn, $sql_check_plant);
    mysqli_stmt_bind_param($stmt_check_plant, "i", $plant_id);
    mysqli_stmt_execute($stmt_check_plant);
    $result_check_plant = mysqli_stmt_get_result($stmt_check_plant);
    
    if (mysqli_num_rows($result_check_plant) == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit();
    }
    
    $plant = mysqli_fetch_assoc($result_check_plant);
    
    // Check if item is already in wishlist
    $sql_check_wishlist = "SELECT * FROM wishlist WHERE user_id = ? AND plant_id = ?";
    $stmt_check_wishlist = mysqli_prepare($conn, $sql_check_wishlist);
    mysqli_stmt_bind_param($stmt_check_wishlist, "ii", $user_id, $plant_id);
    mysqli_stmt_execute($stmt_check_wishlist);
    $result_check_wishlist = mysqli_stmt_get_result($stmt_check_wishlist);
    
    // If already in wishlist, remove it (toggle functionality)
    if (mysqli_num_rows($result_check_wishlist) > 0) {
        $sql_remove = "DELETE FROM wishlist WHERE user_id = ? AND plant_id = ?";
        $stmt_remove = mysqli_prepare($conn, $sql_remove);
        mysqli_stmt_bind_param($stmt_remove, "ii", $user_id, $plant_id);
        
        if (mysqli_stmt_execute($stmt_remove)) {
            // Get updated wishlist count
            $sql_count = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?";
            $stmt_count = mysqli_prepare($conn, $sql_count);
            mysqli_stmt_bind_param($stmt_count, "i", $user_id);
            mysqli_stmt_execute($stmt_count);
            $result_count = mysqli_stmt_get_result($stmt_count);
            $row_count = mysqli_fetch_assoc($result_count);
            
            echo json_encode([
                'success' => true,
                'message' => htmlspecialchars($plant['name']) . ' removed from wishlist',
                'wishlistCount' => $row_count['count'],
                'inWishlist' => false
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to remove item from wishlist'
            ]);
        }
    } else {
        // If not in wishlist, add it
        $sql_add = "INSERT INTO wishlist (user_id, plant_id, added_at) VALUES (?, ?, NOW())";
        $stmt_add = mysqli_prepare($conn, $sql_add);
        mysqli_stmt_bind_param($stmt_add, "ii", $user_id, $plant_id);
        
        if (mysqli_stmt_execute($stmt_add)) {
            // Get updated wishlist count
            $sql_count = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?";
            $stmt_count = mysqli_prepare($conn, $sql_count);
            mysqli_stmt_bind_param($stmt_count, "i", $user_id);
            mysqli_stmt_execute($stmt_count);
            $result_count = mysqli_stmt_get_result($stmt_count);
            $row_count = mysqli_fetch_assoc($result_count);
            
            echo json_encode([
                'success' => true,
                'message' => htmlspecialchars($plant['name']) . ' added to wishlist',
                'wishlistCount' => $row_count['count'],
                'inWishlist' => true
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to add item to wishlist'
            ]);
        }
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?> 