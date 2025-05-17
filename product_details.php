<?php
session_start();
include 'db_connection.php';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: shop.php");
    exit();
}

$product_id = $_GET['id'];
$in_wishlist = false;

// Check if product is in user's wishlist
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $check_wishlist = "SELECT * FROM wishlist WHERE user_id = ? AND plant_id = ?";
    $stmt_wishlist = mysqli_prepare($conn, $check_wishlist);
    if ($stmt_wishlist) {
        mysqli_stmt_bind_param($stmt_wishlist, "ii", $user_id, $product_id);
        mysqli_stmt_execute($stmt_wishlist);
        $result_wishlist = mysqli_stmt_get_result($stmt_wishlist);
        $in_wishlist = mysqli_num_rows($result_wishlist) > 0;
    }
}

// Get product details
$sql_product = "SELECT p.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_name
               FROM plants p
               LEFT JOIN plant_category pc ON p.plant_id = pc.plant_id
               LEFT JOIN categories c ON pc.category_id = c.category_id
               WHERE p.plant_id = ? AND p.status = 1
               GROUP BY p.plant_id";
$stmt_product = mysqli_prepare($conn, $sql_product);
if ($stmt_product) {
    mysqli_stmt_bind_param($stmt_product, "i", $product_id);
    mysqli_stmt_execute($stmt_product);
    $result_product = mysqli_stmt_get_result($stmt_product);

    // Check if product exists
    if (mysqli_num_rows($result_product) == 0) {
        header("Location: shop.php");
        exit();
    }

    $product = mysqli_fetch_assoc($result_product);
} else {
    header("Location: shop.php");
    exit();
}

// Get product images
$product_images = array();

// First, check for main image in plant_images table
$sql_main_image = "SELECT * FROM plant_images WHERE plant_id = ? AND is_main = 1 LIMIT 1";
$stmt_main_image = mysqli_prepare($conn, $sql_main_image);
if ($stmt_main_image) {
    mysqli_stmt_bind_param($stmt_main_image, "i", $product_id);
    mysqli_stmt_execute($stmt_main_image);
    $result_main_image = mysqli_stmt_get_result($stmt_main_image);
    
    if (mysqli_num_rows($result_main_image) > 0) {
        $main_image = mysqli_fetch_assoc($result_main_image);
        $product_images[] = $main_image['image_url']; // Add main image from plant_images as first image
    } else {
        $product_images[] = $product['img_url']; // Add main image from plants table as fallback
    }
}

// Get additional images
$sql_images = "SELECT * FROM plant_images WHERE plant_id = ? AND is_main = 0 ORDER BY image_id LIMIT 3"; // Limit to 3 additional images
$stmt_images = mysqli_prepare($conn, $sql_images);
if ($stmt_images) {
    mysqli_stmt_bind_param($stmt_images, "i", $product_id);
    mysqli_stmt_execute($stmt_images);
    $result_images = mysqli_stmt_get_result($stmt_images);
    
    while ($image = mysqli_fetch_assoc($result_images)) {
        $product_images[] = $image['image_url'];
    }
}

// If no additional images, use main image multiple times
if (count($product_images) <= 1) {
    $product_images = array($product_images[0], $product_images[0], $product_images[0], $product_images[0]);
} 
// Make sure we have exactly 4 images total (trim if more than 4)
else if (count($product_images) > 4) {
    $product_images = array_slice($product_images, 0, 4);
}

// Get similar products (same category)
$sql_similar = "SELECT p.*
               FROM plants p
               JOIN plant_category pc1 ON p.plant_id = pc1.plant_id
               JOIN plant_category pc2 ON pc1.category_id = pc2.category_id
               WHERE pc2.plant_id = ? AND p.plant_id != ? AND p.status = 1 AND p.stock > 0
               GROUP BY p.plant_id
               ORDER BY RAND()
               LIMIT 4";
$stmt_similar = mysqli_prepare($conn, $sql_similar);
if ($stmt_similar) {
    mysqli_stmt_bind_param($stmt_similar, "ii", $product_id, $product_id);
    mysqli_stmt_execute($stmt_similar);
    $result_similar = mysqli_stmt_get_result($stmt_similar);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Kiyara Plants</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="contecnt/css/style.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="contecnt/css/header.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="contecnt/css/footer.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="contecnt/css/product_details.css?v=<?php echo time(); ?>" />
    
    <style>
        .product-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .wishlist-btn {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background: transparent;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .wishlist-btn.active {
            border-color: #F44336;
        }
        
        .wishlist-btn.active svg {
            fill: #F44336;
        }
        
        .wishlist-btn:hover {
            border-color: #F44336;
        }
        
        .wishlist-icon {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            fill: #777;
            transition: fill 0.3s;
        }
        
        .wishlist-btn:hover .wishlist-icon {
            fill: #F44336;
        }
        
        /* Fix Add to Cart hover effect */
        .btn-full:hover {
            background-color: #388E3C !important;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Increase product image size */
        .prodcut-item-img-main {
            width: 100%;
            max-height: 450px;
            object-fit: contain;
        }
    </style>
</head>

<body>
    <?php include 'header.php';?>

    <section class="prodcut-item-main">
        <div class="prodcut-item-main-hero">

            <div class="prodcut-item-details">
                <?php if (!empty($product['origin'])): ?>
                <h3><?php echo htmlspecialchars($product['origin']); ?></h3>
                <?php endif; ?>
                
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <h2>Rs<?php echo number_format($product['price'], 2); ?></h2>
                
                <div class="product-actions">
                    <?php
                    $disabled = '';
                    $button_text = 'ADD TO Cart';
                    if ($product['stock'] <= 0) {
                        $disabled = 'disabled';
                        $button_text = 'Out of Stock';
                    }
                    ?>
                    
                    <a class="btn btn-full <?php echo $disabled; ?>" href="javascript:void(0)" onclick="addToCart(<?php echo $product['plant_id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>', <?php echo $product['price']; ?>)"><?php echo $button_text; ?></a>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <button id="wishlist-btn" class="wishlist-btn <?php echo $in_wishlist ? 'active' : ''; ?>" onclick="toggleWishlist(<?php echo $product['plant_id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')">
                        <svg class="wishlist-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        <?php echo $in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>
                    </button>
                    <?php else: ?>
                    <a href="login.php?redirect=product_details.php?id=<?php echo $product_id; ?>" class="wishlist-btn">
                        <svg class="wishlist-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        Login to Save
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="prodcut-item-img-section">
                <img class="prodcut-item-img-main" id="main-product-image"
                    src="<?php echo htmlspecialchars($product_images[0]); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    
                <!-- Image dots -->
                <div class="smole-button-slide">
                    <?php for ($i = 0; $i < min(4, count($product_images)); $i++): ?>
                    <div class="smole-button-slide-icon <?php echo ($i === 0) ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></div>
                    <?php endfor; ?>
                </div>
                    
                <!-- Gallery images -->
                <div class="prodcut-item-img-sub">
                    <?php for ($i = 0; $i < min(4, count($product_images)); $i++): ?>
                    <img class="prodcut-item-img-sub-imges <?php echo ($i === 0) ? 'active' : ''; ?>"
                        src="<?php echo htmlspecialchars($product_images[$i]); ?>" 
                        alt="<?php echo htmlspecialchars($product['name']); ?> - Image <?php echo $i+1; ?>"
                        onclick="changeMainImage(<?php echo $i; ?>)">
                    <?php endfor; ?>
                </div>
            </div>

            <div class="prodcut-item-treatment">
                <h2>Treatment & Facts</h2>

                <div class="prodcut-item-treatment-main">
                    <svg class="prodcut-item-treatment-svg" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48"
                        id="water-drop">
                        <path
                            d="M24,46A17,17,0,0,1,7,29C7,15.68,22.7,2.77,23.37,2.22a1,1,0,0,1,1.26,0C25.3,2.77,41,15.68,41,29A17,17,0,0,1,24,46ZM24,4.31C21.08,6.87,9,18.07,9,29a15,15,0,0,0,30,0C39,18.07,26.92,6.87,24,4.31Zm0,32.82a9,9,0,0,1-9-9H13a11,11,0,0,0,11,11Z"
                            data-name="17 Water Drop, Drop, Drop Water"></path>
                    </svg>
                    <div class="prodcut-item-treatment-details">
                        <h3>WATERING</h3>
                        <p><?php echo (!empty($product['watering_instructions'])) ? htmlspecialchars($product['watering_instructions']) : 'Water when soil is slightly dry to touch. Avoid overwatering.'; ?></p>
                    </div>
                </div>

                <div class="prodcut-item-treatment-main">
                    <svg class="prodcut-item-treatment-svg" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 64 64"
                        id="sun">
                        <g data-name="Layer 2">
                            <path
                                d="M32 50A18 18 0 1 1 50 32 18 18 0 0 1 32 50zm0-34A16 16 0 1 0 48 32 16 16 0 0 0 32 16zM32 62a1 1 0 0 1-1-1V54a1 1 0 0 1 2 0v7A1 1 0 0 1 32 62zM32 11a1 1 0 0 1-1-1V3a1 1 0 0 1 2 0v7A1 1 0 0 1 32 11zM52.51 53.51a1 1 0 0 1-.71-.29l-4.95-4.95a1 1 0 0 1 1.41-1.41l4.95 4.95a1 1 0 0 1-.71 1.71zM16.44 17.44a1 1 0 0 1-.71-.29L10.79 12.2a1 1 0 0 1 1.41-1.41l4.95 4.95a1 1 0 0 1-.71 1.71zM61 33H54a1 1 0 0 1 0-2h7a1 1 0 0 1 0 2zM10 33H3a1 1 0 0 1 0-2h7a1 1 0 0 1 0 2zM47.56 17.44a1 1 0 0 1-.71-1.71l4.95-4.95a1 1 0 0 1 1.41 1.41l-4.95 4.95A1 1 0 0 1 47.56 17.44zM11.49 53.51a1 1 0 0 1-.71-1.71l4.95-4.95a1 1 0 0 1 1.41 1.41L12.2 53.21A1 1 0 0 1 11.49 53.51z">
                            </path>
                        </g>
                    </svg>
                    <div class="prodcut-item-treatment-details">
                        <h3>LIGHT</h3>
                        <p><?php echo (!empty($product['light_requirements'])) ? htmlspecialchars($product['light_requirements']) : 'Prefers bright, indirect light. Avoid direct sunlight which can scorch the leaves.'; ?></p>
                    </div>
                </div>

                <div class="prodcut-item-treatment-main">
                    <svg class="prodcut-item-treatment-svg" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 256 256">
                        <g transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                            <path
                                d="M 64.44 12.016 c 5.225 0 10.136 2.035 13.831 5.729 c 7.626 7.626 7.626 20.035 0 27.662 l -19.44 19.44 L 45 78.677 L 31.169 64.846 l -19.44 -19.44 c -7.626 -7.626 -7.626 -20.035 0 -27.662 c 3.694 -3.694 8.606 -5.729 13.831 -5.729 c 5.225 0 10.136 2.035 13.831 5.729 l 1.367 1.367 L 45 23.354 l 4.242 -4.242 l 1.367 -1.367 C 54.304 14.05 59.216 12.016 64.44 12.016 M 64.44 6.016 c -6.541 0 -13.083 2.495 -18.073 7.486 L 45 14.869 l 0 0 l 0 0 l -1.367 -1.367 C 38.642 8.511 32.101 6.016 25.56 6.016 S 12.477 8.511 7.486 13.502 c -9.982 9.982 -9.982 26.165 0 36.147 l 19.44 19.44 c 0 0 0 0 0.001 0 L 45 87.163 l 18.073 -18.073 c 0 0 0 0 0 0 l 19.44 -19.44 c 9.982 -9.982 9.982 -26.165 0 -36.147 C 77.523 8.511 70.982 6.016 64.44 6.016 L 64.44 6.016 z"
                                style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                                transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        </g>
                    </svg>
                    <div class="prodcut-item-treatment-details">
                        <h3>BENEFITS</h3>
                        <p><?php echo (!empty($product['benefits'])) ? htmlspecialchars($product['benefits']) : 'Purifies air by removing toxins. Brings natural beauty to any space.'; ?></p>
                    </div>
                </div>

                <div class="prodcut-item-treatment-main">
                    <svg class="prodcut-item-treatment-svg" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 256 256">
                        <g transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                            <path
                                d="M 62.882 86.269 c -4.713 0 -9.138 -1.939 -12.46 -5.458 c -1.452 -1.54 -3.376 -2.389 -5.413 -2.389 c -2.038 0 -3.961 0.849 -5.413 2.388 c -3.321 3.52 -7.746 5.459 -12.46 5.459 s -9.139 -1.939 -12.46 -5.458 c -6.783 -7.19 -6.783 -18.887 0 -26.075 l 9.205 -10.435 c 5.522 -6.258 13.027 -9.706 21.129 -9.706 c 0 0 0 0 0 0 c 8.102 0 15.606 3.447 21.128 9.706 l 9.229 10.461 c 6.759 7.191 6.751 18.868 -0.023 26.049 C 72.021 84.331 67.596 86.269 62.882 86.269 z M 45.009 74.422 c 3.151 0 6.106 1.294 8.324 3.644 c 2.557 2.71 5.949 4.203 9.549 4.203 c 3.601 0 6.993 -1.492 9.55 -4.204 c 5.354 -5.675 5.354 -14.909 0 -20.582 l -0.045 -0.05 l -9.251 -10.485 c -4.751 -5.386 -11.189 -8.351 -18.128 -8.352 c -6.939 0 -13.377 2.966 -18.129 8.352 l -9.25 10.485 c -5.4 5.724 -5.4 14.957 -0.045 20.633 c 2.558 2.71 5.95 4.203 9.55 4.203 s 6.992 -1.492 9.55 -4.204 C 38.902 75.716 41.858 74.422 45.009 74.422 z"
                                style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                                transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            <path
                                d="M 32.462 30.395 c -4.796 0 -9.604 -2.901 -11.106 -9.966 C 19.7 12.633 23.327 5.234 29.443 3.934 c 6.119 -1.301 12.441 3.985 14.098 11.779 c 1.77 8.327 -3.009 13.237 -8.53 14.411 C 34.175 30.302 33.319 30.395 32.462 30.395 z M 31.34 7.736 c -0.358 0 -0.714 0.037 -1.064 0.111 c -3.959 0.841 -6.204 6.113 -5.006 11.75 c 1.084 5.097 4.414 7.567 8.91 6.615 c 4.495 -0.956 6.532 -4.569 5.449 -9.666 l 0 0 c -0.603 -2.833 -1.975 -5.303 -3.866 -6.954 C 34.369 8.374 32.831 7.736 31.34 7.736 z"
                                style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                                transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            <path
                                d="M 57.555 30.395 c -0.856 0 -1.711 -0.092 -2.548 -0.27 c -5.521 -1.174 -10.3 -6.085 -8.53 -14.412 c 0.78 -3.674 2.609 -6.919 5.147 -9.135 c 2.7 -2.358 5.877 -3.299 8.95 -2.644 c 3.071 0.652 5.593 2.803 7.101 6.056 c 1.417 3.058 1.768 6.765 0.986 10.439 C 67.16 27.493 62.351 30.395 57.555 30.395 z M 58.678 7.736 c -1.491 0 -3.029 0.638 -4.423 1.856 c -1.891 1.651 -3.263 4.12 -3.866 6.954 c -1.083 5.097 0.953 8.71 5.449 9.666 c 4.496 0.953 7.826 -1.517 8.91 -6.615 c 0.602 -2.833 0.353 -5.647 -0.703 -7.924 c -0.966 -2.082 -2.494 -3.441 -4.303 -3.826 C 59.392 7.772 59.036 7.736 58.678 7.736 z"
                                style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                                transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            <path
                                d="M 77.828 46.507 c -1.726 0 -3.433 -0.407 -4.988 -1.133 c -5.116 -2.385 -8.667 -8.245 -5.07 -15.961 c 1.587 -3.404 4.098 -6.154 7.07 -7.743 c 3.162 -1.69 6.47 -1.89 9.316 -0.563 c 5.667 2.643 7.536 10.669 4.169 17.892 l 0 0 C 85.821 44.368 81.778 46.507 77.828 46.507 z M 80.215 24.247 c -1.117 0 -2.305 0.319 -3.489 0.952 c -2.213 1.183 -4.107 3.281 -5.331 5.906 c -2.202 4.723 -1.029 8.702 3.135 10.644 c 4.164 1.943 7.967 0.282 10.169 -4.441 c 2.436 -5.223 1.434 -10.865 -2.235 -12.575 C 81.768 24.408 81.009 24.247 80.215 24.247 z"
                                style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                                transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            <path
                                d="M 12.156 46.529 c -1.238 0 -2.453 -0.214 -3.601 -0.643 c -2.893 -1.083 -5.266 -3.464 -6.862 -6.887 c -1.588 -3.404 -2.08 -7.095 -1.387 -10.393 c 0.737 -3.508 2.71 -6.171 5.556 -7.498 c 2.845 -1.328 6.153 -1.127 9.315 0.563 c 2.972 1.589 5.482 4.339 7.07 7.743 l 0 0 c 1.596 3.423 1.895 6.771 0.865 9.684 c -0.959 2.71 -3.066 4.94 -5.935 6.277 C 15.533 46.142 13.823 46.529 12.156 46.529 z M 9.803 24.247 c -0.795 0 -1.553 0.161 -2.25 0.486 c -3.668 1.71 -4.67 7.352 -2.234 12.575 c 1.144 2.453 2.748 4.123 4.638 4.831 c 1.704 0.639 3.616 0.503 5.53 -0.39 c 1.914 -0.893 3.247 -2.271 3.854 -3.986 c 0.673 -1.903 0.425 -4.205 -0.719 -6.658 l 0 0 c -1.224 -2.625 -3.117 -4.722 -5.331 -5.906 C 12.109 24.566 10.92 24.247 9.803 24.247 z"
                                style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,0,0); fill-rule: nonzero; opacity: 1;"
                                transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        </g>
                    </svg>
                    <div class="prodcut-item-treatment-details">
                        <h3>ANIMAL FRIENDLINESS</h3>
                        <p><?php echo (!empty($product['pet_friendly']) && $product['pet_friendly'] == 1) ? 'This plant is safe for pets and animals.' : 'This plant may be harmful to pets if ingested. Keep away from animals.'; ?></p>
                    </div>
                </div>

                <div class="prodcut-item-treatment-main prodcut-item-treatment-main-last">
                    <svg class="prodcut-item-treatment-svg" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 256 256">
                        <g transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                            <path
                                d="M 75.546 78.738 H 14.455 C 6.484 78.738 0 72.254 0 64.283 V 25.716 c 0 -7.97 6.485 -14.455 14.455 -14.455 h 61.091 c 7.97 0 14.454 6.485 14.454 14.455 v 38.567 C 90 72.254 83.516 78.738 75.546 78.738 z M 14.455 15.488 c -5.64 0 -10.228 4.588 -10.228 10.228 v 38.567 c 0 5.64 4.588 10.229 10.228 10.229 h 61.091 c 5.64 0 10.228 -4.589 10.228 -10.229 V 25.716 c 0 -5.64 -4.588 -10.228 -10.228 -10.228 H 14.455 z"
                                style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(29,29,27); fill-rule: nonzero; opacity: 1;"
                                transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            <path
                                d="M 11.044 25.917 C 21.848 36.445 32.652 46.972 43.456 57.5 c 2.014 1.962 5.105 -1.122 3.088 -3.088 C 35.74 43.885 24.936 33.357 14.132 22.83 C 12.118 20.867 9.027 23.952 11.044 25.917 L 11.044 25.917 z"
                                style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(29,29,27); fill-rule: nonzero; opacity: 1;"
                                transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            <path
                                d="M 46.544 57.5 c 10.804 -10.527 21.608 -21.055 32.412 -31.582 c 2.016 -1.965 -1.073 -5.051 -3.088 -3.088 C 65.064 33.357 54.26 43.885 43.456 54.412 C 41.44 56.377 44.529 59.463 46.544 57.5 L 46.544 57.5 z"
                                style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(29,29,27); fill-rule: nonzero; opacity: 1;"
                                transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            <path
                                d="M 78.837 64.952 c -7.189 -6.818 -14.379 -13.635 -21.568 -20.453 c -2.039 -1.933 -5.132 1.149 -3.088 3.088 c 7.189 6.818 14.379 13.635 21.568 20.453 C 77.788 69.973 80.881 66.89 78.837 64.952 L 78.837 64.952 z"
                                style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(29,29,27); fill-rule: nonzero; opacity: 1;"
                                transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                            <path
                                d="M 14.446 68.039 c 7.189 -6.818 14.379 -13.635 21.568 -20.453 c 2.043 -1.938 -1.048 -5.022 -3.088 -3.088 c -7.189 6.818 -14.379 13.635 -21.568 20.453 C 9.315 66.889 12.406 69.974 14.446 68.039 L 14.446 68.039 z"
                                style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(29,29,27); fill-rule: nonzero; opacity: 1;"
                                transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                        </g>
                    </svg>
                    <div class="prodcut-item-treatment-details">
                        <h3>WANNA KNOW MORE?</h3>
                        <p>Contact our plant specialists for detailed care instructions and tips for this plant.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php';?>

    <script>
        // Function to change main product image
        function changeMainImage(index) {
            // Get all product images
            const images = <?php echo json_encode($product_images); ?>;
            const mainImage = document.getElementById('main-product-image');
            
            // Change main image src
            mainImage.src = images[index];
            
            // Update active states for thumbnails
            const thumbnails = document.querySelectorAll('.prodcut-item-img-sub-imges');
            thumbnails.forEach((thumb, i) => {
                if (i === index) {
                    thumb.classList.add('active');
                } else {
                    thumb.classList.remove('active');
                }
            });
            
            // Update active states for dots
            const dots = document.querySelectorAll('.smole-button-slide-icon');
            dots.forEach((dot, i) => {
                if (i === index) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }
        
        // Add click event listeners to dots
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure all images are loaded properly
            const galleryImages = document.querySelectorAll('.prodcut-item-img-sub-imges');
            const mainImage = document.getElementById('main-product-image');
            
            // Fix any broken images by setting fallback
            if (mainImage.naturalWidth === 0) {
                mainImage.onerror = function() {
                    this.src = 'contecnt/img/prodcuts/product-placeholder.jpg';
                }
            }
            
            galleryImages.forEach((img, index) => {
                // Handle image loading errors
                img.onerror = function() {
                    this.src = 'contecnt/img/prodcuts/product-placeholder.jpg';
                };
            });
            
            // Add click handlers to dots
            const dots = document.querySelectorAll('.smole-button-slide-icon');
            dots.forEach((dot) => {
                dot.addEventListener('click', function() {
                    const index = parseInt(this.getAttribute('data-index'));
                    changeMainImage(index);
                });
            });
        });
        
        // Add to Cart function
        function addToCart(plant_id, name, price) {
            if (<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                // Send AJAX request to add item to cart
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'add_to_cart.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                // Update cart count
                                const cartCountElement = document.getElementById('cart-count');
                                if (cartCountElement) {
                                    cartCountElement.textContent = response.cartCount;
                                    cartCountElement.style.display = response.cartCount > 0 ? '' : 'none';
                                }
                                
                                // Show success message
                                alert(name + ' added to cart successfully!');
                            } else {
                                // Show error message
                                alert(response.message);
                            }
                        } catch (e) {
                            console.error('Error parsing JSON response:', e);
                            alert('An error occurred. Please try again.');
                        }
                    } else {
                        alert('Request failed. Please try again.');
                    }
                };
                xhr.onerror = function() {
                    alert('Request failed. Please check your connection.');
                };
                xhr.send('plant_id=' + encodeURIComponent(plant_id) + '&quantity=1');
            } else {
                // Redirect to login page
                window.location.href = 'login.php?redirect=product_details.php?id=' + plant_id;
            }
        }
        
        // Toggle Wishlist function
        function toggleWishlist(plant_id, name) {
            // Send AJAX request to toggle wishlist status
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_to_wishlist.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        const wishlistButton = document.getElementById('wishlist-btn');
                        
                        if (response.success) {
                            // Update wishlist icon and text
                            if (response.inWishlist) {
                                wishlistButton.classList.add('active');
                                wishlistButton.innerHTML = `
                                    <svg class="wishlist-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                    Remove from Wishlist
                                `;
                            } else {
                                wishlistButton.classList.remove('active');
                                wishlistButton.innerHTML = `
                                    <svg class="wishlist-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                    Add to Wishlist
                                `;
                            }
                            
                            // Update wishlist count in header if exists
                            const wishlistCountElement = document.getElementById('wishlist-count');
                            if (wishlistCountElement) {
                                wishlistCountElement.textContent = response.wishlistCount;
                                wishlistCountElement.style.display = response.wishlistCount > 0 ? '' : 'none';
                            }
                            
                            // Show success message
                            alert(response.message);
                        } else {
                            // Show error message
                            alert(response.message);
                        }
                    } catch (e) {
                        console.error('Error parsing JSON response:', e);
                        alert('An error occurred. Please try again.');
                    }
                } else {
                    alert('Request failed. Please try again.');
                }
            };
            xhr.onerror = function() {
                alert('Request failed. Please check your connection.');
            };
            xhr.send('plant_id=' + encodeURIComponent(plant_id));
        }
    </script>
</body>

</html>