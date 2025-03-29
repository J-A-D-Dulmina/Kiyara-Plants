<?php
// Check if session is already started before calling session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Always include the database connection
include_once 'db_connection.php';

// Get site settings
$settings = array(
    'store_name' => 'Kiyara Plants',
    'store_email' => 'info@kiyaraplants.com',
    'store_phone' => '+94 77 123 4567',
    'currency' => 'Rs.',
    'meta_title' => 'Kiyara Plants - Beautiful Plants for Your Home',
    'meta_description' => 'Buy beautiful plants online from Kiyara Plants. We offer a wide range of indoor and outdoor plants at affordable prices.',
    'meta_keywords' => 'plants, indoor plants, outdoor plants, gardening, home decor',
);

// Check if settings table exists and get settings
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

// Check for maintenance mode
if (isset($db_settings['maintenance_mode']) && $db_settings['maintenance_mode'] == 1) {
    // Allow admin to access site during maintenance
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    if (!$is_admin && basename($_SERVER['PHP_SELF']) != 'maintenance.php') {
        header("Location: maintenance.php");
        exit;
    }
}

$userLink = '<a class="user-name" href="login.php">Login</a>'; // Default link
$userImage = 'contecnt/img/Users/User test icon.png'; // Default image 
$userImageLink = 'login.php'; // Default link for user image
$customerName = '';
$email = '';
$phone = '';
$address = '';
$age = '';
$postal_code = '';

if (isset($_SESSION["user_id"])) {
    $customerId = $_SESSION["user_id"];

    $sql = "SELECT name, email, password, address, phone, profile_image, age, postal_code FROM customers WHERE customer_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $customerId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $customerName = $row['name'];
            $email = $row['email'];
            $phone = $row['phone'];
            $address = $row['address'];
            $age = $row['age'];
            $postal_code = $row['postal_code'];
            $userImage = $row['profile_image'] ? $row['profile_image'] : $userImage; // Use the fetched image URL if available

            // Replace the "Login" link with the customer's name linked to user.php
            $userLink = '<a class="user-name" href="user.php">' . htmlspecialchars($customerName) . '</a>';
            $userImageLink = 'user.php';
        }
    }
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION["user_id"]);

// Get cart count if user is logged in
$cartCount = 0;
if ($isLoggedIn) {
    $user_id = $_SESSION["user_id"];
    $sql_count = "SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?";
    $stmt_count = mysqli_prepare($conn, $sql_count);
    
    if ($stmt_count) {
        mysqli_stmt_bind_param($stmt_count, "i", $user_id);
        mysqli_stmt_execute($stmt_count);
        $result_count = mysqli_stmt_get_result($stmt_count);
        $row_count = mysqli_fetch_assoc($result_count);
        $cartCount = $row_count['total'] ? (int)$row_count['total'] : 0;
    }
}

// Get wishlist count if user is logged in
$wishlistCount = 0;
if ($isLoggedIn) {
    $user_id = $_SESSION["user_id"];
    $sql_wishlist = "SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?";
    $stmt_wishlist = mysqli_prepare($conn, $sql_wishlist);
    
    if ($stmt_wishlist) {
        mysqli_stmt_bind_param($stmt_wishlist, "i", $user_id);
        mysqli_stmt_execute($stmt_wishlist);
        $result_wishlist = mysqli_stmt_get_result($stmt_wishlist);
        $row_wishlist = mysqli_fetch_assoc($result_wishlist);
        $wishlistCount = $row_wishlist['total'] ? (int)$row_wishlist['total'] : 0;
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['meta_title']); ?></title>
    
    <!-- Meta tags for SEO -->
    <meta name="description" content="<?php echo htmlspecialchars($settings['meta_description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($settings['meta_keywords']); ?>">
    
    <!-- Favicon if set -->
    <?php if (!empty($settings['store_favicon'])): ?>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($settings['store_favicon']); ?>" type="image/x-icon">
    <?php endif; ?>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">


    <link rel="stylesheet" href="contecnt/css/header.css" />
    <style>
    .main-nav-list a .wishlist-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
        background-color: #fcdb1b;  /* Yellow background */
        color: #000;
        border-radius: 50%;
        font-size: 12px;
        position: absolute;
        top: -5px;
        right: -5px;
    }
    
    .wishlist-badge-box {
        position: relative;
    }
    
    .cart-badge-box {
        position: relative;
    }
    
    /* Match wishlist badge to cart badge with yellow background */
    .wishlist-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #fcdb1b; /* Yellow background */
        color: #000;
        border-radius: 50%;
        font-size: 12px;
        min-width: 18px;
        height: 18px;
        font-weight: 500;
        padding: 0 4px;
    }
    
    /* Match cart badge styling to use pink */
    .cart-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #ff7bac; /* Pink background */
        color: #fff; /* White text color */
        border-radius: 50%;
        font-size: 12px;
        min-width: 18px;
        height: 18px;
        font-weight: 500;
        padding: 0 4px;
    }
    </style>
</head>


<body>

    <header class="header">
        <?php if (!empty($settings['store_logo'])): ?>
        <img class="logo" alt="<?php echo htmlspecialchars($settings['store_name']); ?> Logo" src="<?php echo htmlspecialchars($settings['store_logo']); ?>" />
        <?php else: ?>
        <img class="logo" alt="Kiyara Plants Logo" src="contecnt/img/kiyara Plants logo.png" />
        <?php endif; ?>
        <nav class="main-nav">
            <ul class="main-nav-list">
                <li><a class="main-nav-link home" href="index.php">Home</a></li>
                <li><a class="main-nav-link shop" href="shop.php">Shop</a></li>
                <li><a class="main-nav-link about" href="about.php">About</a></li>
                <li><a class="main-nav-link contact" href="contact.php">Contact Us</a></li>
            </ul>
        </nav>
        <div class="login-search-cart">
            <div class="login">
                <div class="login-details">

                    <div class="search-container" id="search-container">
                        <div class="search-icon log-icons" id="search-icon">
                            <svg class="search-bard-icon log-icons-icon" xmlns="http://www.w3.org/2000/svg" id="search" x="0" y="0" version="1.1" viewBox="0 0 29 29" xml:space="preserve">
                                <circle cx="11.854" cy="11.854" r="9" fill="none" stroke="#000" stroke-miterlimit="10" stroke-width="2"></circle>
                                <path fill="none" stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-miterlimit="10" stroke-width="2" d="M18.451 18.451l7.695 7.695"></path>
                            </svg>
                        </div>
                        <input type="text" id="search-input" class="search-input" placeholder="Search...">
                    </div>




                    <a href="wishlist.php" class="log-icons">
                        <div class="wishlist-badge-box">
                            <?php if ($wishlistCount > 0): ?>
                            <span class="wishlist-badge" id="wishlist-count"><?php echo $wishlistCount; ?></span>
                            <?php endif; ?>
                        </div>
                        <svg class="log-icons-icon" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="3rem" height="3rem" viewBox="0 0 256 256" xml:space="preserve">
                            <g style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;" transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                                <path d="M 45 85.465 L 7.098 47.563 C 2.521 42.986 0 36.9 0 30.426 c 0 -6.473 2.521 -12.559 7.099 -17.136 c 4.577 -4.577 10.663 -7.098 17.136 -7.098 s 12.559 2.521 17.137 7.098 L 45 16.919 l 3.63 -3.629 c 4.576 -4.577 10.662 -7.098 17.136 -7.098 s 12.56 2.521 17.136 7.099 v 0 c 0 0 0 0 0 0 C 87.479 17.867 90 23.953 90 30.426 c 0 6.474 -2.521 12.56 -7.099 17.136 L 45 85.465 z M 24.234 14.192 c -4.336 0 -8.413 1.688 -11.479 4.755 C 9.689 22.013 8 26.09 8 30.426 c 0 4.336 1.689 8.414 4.755 11.479 L 45 74.15 l 32.244 -32.244 C 80.312 38.84 82 34.763 82 30.426 c 0 -4.336 -1.688 -8.413 -4.755 -11.479 v 0 c -3.066 -3.066 -7.144 -4.755 -11.479 -4.755 s -8.413 1.688 -11.479 4.755 L 45 28.233 l -9.286 -9.286 C 32.647 15.881 28.571 14.192 24.234 14.192 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            </g>
                        </svg>
                    </a>


                    <a class="log-icons cart-icon" href="cart.php">
                        <div class="cart-badge-box">
                            <?php if ($cartCount > 0): ?>
                            <span class="cart-badge" id="cart-count"><?php echo $cartCount; ?></span>
                            <?php endif; ?>
                        </div>
                        <svg class="log-icons-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" id="cart">
                            <g fill="#000">
                                <path d="M48.5 45.7H18.2c-.5 0-.9-.2-1.1-.6-.3-.4-.3-.9-.1-1.3l2.6-6.6L17 12.6H8.6V9.8h9.6c.7 0 1.3.5 1.4 1.2l2.8 26.1c0 .2 0 .4-.1.7l-2 5h28.2v2.9">
                                </path>
                                <path d="m21.3 38.8-.6-2.7 31.9-6.6V18.2h-33v-2.8H54c.8 0 1.4.6 1.4 1.4v13.8c0 .7-.5 1.2-1.1 1.3l-33 6.9M49.9 54c-3 0-5.5-2.5-5.5-5.5s2.5-5.5 5.5-5.5 5.5 2.5 5.5 5.5-2.5 5.5-5.5 5.5zm0-8.3c-1.5 0-2.8 1.2-2.8 2.8s1.2 2.8 2.8 2.8 2.8-1.2 2.8-2.8-1.3-2.8-2.8-2.8zm-33 8.3c-3 0-5.5-2.5-5.5-5.5s2.5-5.5 5.5-5.5 5.5 2.5 5.5 5.5-2.5 5.5-5.5 5.5zm0-8.3c-1.5 0-2.8 1.2-2.8 2.8s1.2 2.8 2.8 2.8 2.8-1.2 2.8-2.8-1.3-2.8-2.8-2.8z">
                                </path>
                            </g>
                        </svg>



                    </a>
                    <?php echo $userLink; ?>
                    <!-- <a class="user-name " href="login.php">Login</a> -->
                </div>
                <a href="<?php echo $userImageLink; ?>" class="user-img-box">
                    <img src="<?php echo $userImage; ?>" class="user-img" alt="User Profile Image" />
                </a>
            </div>
            <div>

            </div>
            <div>

            </div>
        </div>

    </header>
    <script src="contecnt/js/header.js"></script>

    <?php if (isset($_SESSION["user_id"])): ?>
    <script>
        // Assign PHP variables to JavaScript variables
        var customerName = "<?php echo addslashes($customerName); ?>";
        var email = "<?php echo addslashes($email); ?>";
        var age = "<?php echo addslashes($age); ?>";
        var phone = "<?php echo addslashes($phone); ?>";
        var address = "<?php echo addslashes($address); ?>";
        var postal_code = "<?php echo addslashes($postal_code); ?>";
        var userImage = "<?php echo addslashes($userImage); ?>";
    </script>
    <?php endif; ?>

</body>


</html>