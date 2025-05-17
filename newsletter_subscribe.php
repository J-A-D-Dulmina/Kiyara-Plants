<?php
session_start();
include 'db_connection.php';

// Initialize response variables
$response = array();
$response['success'] = false;
$response['message'] = 'Something went wrong. Please try again.';

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        // Check if email is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Please enter a valid email address.';
        } else {
            // Check if the newsletter_subscribers table exists
            $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'newsletter_subscribers'");
            
            if (mysqli_num_rows($table_check) == 0) {
                // Create the table if it doesn't exist
                $create_table = "CREATE TABLE `newsletter_subscribers` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `email` varchar(255) NOT NULL,
                    `status` tinyint(1) NOT NULL DEFAULT '1',
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `email` (`email`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                mysqli_query($conn, $create_table);
            }
            
            // Check if email already exists
            $check_sql = "SELECT * FROM newsletter_subscribers WHERE email = ?";
            $stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                // Email already subscribed
                $response['message'] = 'This email is already subscribed to our newsletter.';
                
                // Update status to active if it was inactive
                $subscriber = mysqli_fetch_assoc($result);
                if ($subscriber['status'] == 0) {
                    $update_sql = "UPDATE newsletter_subscribers SET status = 1 WHERE email = ?";
                    $stmt = mysqli_prepare($conn, $update_sql);
                    mysqli_stmt_bind_param($stmt, "s", $email);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $response['success'] = true;
                        $response['message'] = 'Your subscription has been reactivated!';
                    }
                } else {
                    // Already active subscription
                    $response['success'] = true;
                }
            } else {
                // New subscriber - add to database
                $insert_sql = "INSERT INTO newsletter_subscribers (email) VALUES (?)";
                $stmt = mysqli_prepare($conn, $insert_sql);
                mysqli_stmt_bind_param($stmt, "s", $email);
                
                if (mysqli_stmt_execute($stmt)) {
                    $response['success'] = true;
                    $response['message'] = 'Thank you for subscribing to our newsletter!';
                } else {
                    $response['message'] = 'Database error: ' . mysqli_error($conn);
                }
            }
        }
    } else {
        $response['message'] = 'Please enter your email address.';
    }
}

// Determine if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($is_ajax) {
    // If it's an AJAX request, return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // If it's a regular form submission, set session message and redirect
    if ($response['success']) {
        $_SESSION['newsletter_success'] = $response['message'];
    } else {
        $_SESSION['newsletter_error'] = $response['message'];
    }
    
    // Redirect back to the referring page or home
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
    header("Location: $redirect");
}
exit;
?> 