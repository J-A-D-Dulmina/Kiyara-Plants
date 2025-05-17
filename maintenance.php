<?php
session_start();
include 'db_connection.php';

// Check if settings table exists and get settings
$settings = array(
    'store_name' => 'Kiyara Plants',
    'store_email' => 'info@kiyaraplants.com',
    'store_phone' => '+94 77 123 4567',
    'maintenance_mode' => 1 // Default to on since we're on this page
);

// Get settings from database if available
$settings_check = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
if (mysqli_num_rows($settings_check) > 0) {
    $settings_query = mysqli_query($conn, "SELECT * FROM settings WHERE id = 1");
    if ($settings_query && mysqli_num_rows($settings_query) > 0) {
        $db_settings = mysqli_fetch_assoc($settings_query);
        // Update settings with database values
        foreach ($db_settings as $key => $value) {
            if (array_key_exists($key, $settings) && !empty($value)) {
                $settings[$key] = $value;
            }
        }
    }
}

// Redirect to homepage if maintenance mode is off and user is not an admin
if ($settings['maintenance_mode'] == 0 && (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1)) {
    header("Location: index.php");
    exit;
}

// Allow admin to bypass maintenance mode
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
    header("Location: admin_settings.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - <?php echo htmlspecialchars($settings['store_name']); ?></title>
    <style>
        body {
            font-family: 'Inter', 'Poppins', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            flex-direction: column;
            color: #333;
            text-align: center;
        }
        .maintenance-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 90%;
        }
        .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 30px;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #28a745;
        }
        p {
            font-size: 1.2rem;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .contact {
            margin-top: 30px;
            font-size: 1rem;
            padding: 10px;
            background-color: #f2f2f2;
            border-radius: 5px;
        }
        .icon {
            font-size: 5rem;
            margin-bottom: 20px;
            color: #28a745;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .loading {
            border: 6px solid #f3f3f3;
            border-top: 6px solid #28a745;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
            margin: 20px auto;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <?php if (!empty($settings['store_logo'])): ?>
            <img src="<?php echo htmlspecialchars($settings['store_logo']); ?>" alt="<?php echo htmlspecialchars($settings['store_name']); ?> Logo" class="logo">
        <?php else: ?>
            <img src="contecnt/img/kiyara Plants logo.png" alt="Kiyara Plants Logo" class="logo">
        <?php endif; ?>
        
        <h1>We'll Be Back Soon!</h1>
        
        <div class="loading"></div>
        
        <p>We're currently performing maintenance on our website to improve your shopping experience.</p>
        <p>Please check back shortly. We apologize for any inconvenience.</p>
        
        <div class="contact">
            <p>Need assistance? Contact us:</p>
            <p>Email: <?php echo htmlspecialchars($settings['store_email']); ?></p>
            <p>Phone: <?php echo htmlspecialchars($settings['store_phone']); ?></p>
        </div>
    </div>
</body>
</html> 