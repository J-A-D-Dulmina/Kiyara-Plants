<?php
// Check if session is already started before calling session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db_connection.php';

// Get all categories for filter
$sql_categories = "SELECT c.* FROM categories c 
                  JOIN plant_category pc ON c.category_id = pc.category_id
                  JOIN plants p ON pc.plant_id = p.plant_id
                  WHERE p.status = 1
                  GROUP BY c.category_id
                  ORDER BY c.name";
$result_categories = mysqli_query($conn, $sql_categories);
$categories = [];
if ($result_categories) {
    while ($row = mysqli_fetch_assoc($result_categories)) {
        $categories[] = $row;
    }
}

// Initialize filter variables
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'name_asc';
$rating_filter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;

// Get price range for filter (do this before applying min/max price filters)
$sql_price_range = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM plants WHERE status = 1";
$result_price_range = mysqli_query($conn, $sql_price_range);
$price_range = mysqli_fetch_assoc($result_price_range);

$db_min_price = floor($price_range['min_price']);
$db_max_price = ceil($price_range['max_price']);

// If min/max price not explicitly set in URL or form submission, use DB values
if (!isset($_GET['min_price'])) {
    $min_price = $db_min_price;
}
if (!isset($_GET['max_price'])) {
    $max_price = $db_max_price; 
}

// Build query conditions based on filters
$where_conditions = ["p.status = 1"];
$params = [];
$types = "";

if (!empty($category_filter)) {
    $where_conditions[] = "pc.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

if (!empty($search_query)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// Only add price range if a filter was explicitly applied
if (isset($_GET['min_price']) || isset($_GET['max_price'])) {
    $where_conditions[] = "p.price BETWEEN ? AND ?";
    $params[] = $min_price;
    $params[] = $max_price;
    $types .= "dd";
}

// Add rating filter if selected
if ($rating_filter > 0) {
    // Assuming you have a ratings table or field in your database
    // Adjust this condition according to your actual database structure
    $where_conditions[] = "p.rating >= ?";
    $params[] = $rating_filter;
    $types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);

// Sort order
$order_by = "p.name ASC"; // Default
if ($sort_by == 'price_asc') {
    $order_by = "p.price ASC";
} elseif ($sort_by == 'price_desc') {
    $order_by = "p.price DESC";
} elseif ($sort_by == 'name_desc') {
    $order_by = "p.name DESC";
} elseif ($sort_by == 'newest') {
    $order_by = "p.plant_id DESC";
} elseif ($sort_by == 'popular') {
    $order_by = "p.sales_count DESC"; // Assuming you have a sales count column
}

// Pagination setup
$items_per_page = 20;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get wishlist items for this user if logged in
$user_wishlist = array();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql_wishlist = "SELECT plant_id FROM wishlist WHERE user_id = ?";
    $stmt_wishlist = mysqli_prepare($conn, $sql_wishlist);
    mysqli_stmt_bind_param($stmt_wishlist, "i", $user_id);
    mysqli_stmt_execute($stmt_wishlist);
    $result_wishlist = mysqli_stmt_get_result($stmt_wishlist);
    
    while ($wishlist_item = mysqli_fetch_assoc($result_wishlist)) {
        $user_wishlist[] = $wishlist_item['plant_id'];
    }
}

// Get total products count with filters
$sql_count = "SELECT COUNT(DISTINCT p.plant_id) as total 
              FROM plants p
              LEFT JOIN plant_category pc ON p.plant_id = pc.plant_id
              LEFT JOIN categories c ON pc.category_id = c.category_id
              WHERE $where_clause";
$stmt_count = mysqli_prepare($conn, $sql_count);

if (!empty($params) && $stmt_count) {
    // Create array of references for bind_param
    $bind_types = $types;
    $bind_params = array();
    $bind_params[] = &$bind_types;
    
    for ($i = 0; $i < count($params); $i++) {
        $bind_params[] = &$params[$i];
    }
    
    call_user_func_array(array($stmt_count, 'bind_param'), $bind_params);
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $row_count = mysqli_fetch_assoc($result_count);
    $total_products = $row_count['total'];
} else {
    $total_products = 0;
}

$total_pages = ceil($total_products / $items_per_page);

// Get products with filters and pagination
$sql_products = "SELECT p.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_name,
                (SELECT pi.image_url FROM plant_images pi WHERE pi.plant_id = p.plant_id AND pi.is_main = 1 LIMIT 1) as main_image_url
                FROM plants p
                LEFT JOIN plant_category pc ON p.plant_id = pc.plant_id
                LEFT JOIN categories c ON pc.category_id = c.category_id
                WHERE $where_clause
                GROUP BY p.plant_id
                ORDER BY $order_by
                LIMIT ? OFFSET ?";

$stmt_products = mysqli_prepare($conn, $sql_products);

// Add pagination params
$params_with_pagination = $params;
$params_with_pagination[] = $items_per_page;
$params_with_pagination[] = $offset;
$types_with_pagination = $types . "ii";

if ($stmt_products) {
    // Create array of references for bind_param
    $bind_types = $types_with_pagination;
    $bind_params = array();
    $bind_params[] = &$bind_types;
    
    for ($i = 0; $i < count($params_with_pagination); $i++) {
        $bind_params[] = &$params_with_pagination[$i];
    }
    
    call_user_func_array(array($stmt_products, 'bind_param'), $bind_params);
    mysqli_stmt_execute($stmt_products);
    $result_products = mysqli_stmt_get_result($stmt_products);
} else {
    $result_products = false;
}

// Debug information (uncomment in development)
echo '<div style="display:none;" id="debug-info" class="debug-info">';
echo '<h5>Debug Information</h5>';
echo '<pre>';
echo 'Products Query: ' . $sql_products . "\n\n";
echo 'Where Clause: ' . $where_clause . "\n\n";
echo 'Order By: ' . $order_by . "\n\n";
echo 'Total Products: ' . $total_products . "\n\n";
echo 'Min Price: ' . $min_price . ', Max Price: ' . $max_price . "\n\n";
echo 'Params: ';
print_r($params);
echo "\n\n";
// Check if database connection is working
echo 'Database Connection: ' . (mysqli_ping($conn) ? 'Connected' : 'Not Connected') . "\n\n";
// Check for available products in database
$check_query = "SELECT COUNT(*) as count FROM plants WHERE status = 1";
$check_result = mysqli_query($conn, $check_query);
$check_count = mysqli_fetch_assoc($check_result);
echo 'Total Active Products in Database: ' . $check_count['count'] . "\n\n";
if ($result_products) {
    echo 'Products Found in Current Query: ' . mysqli_num_rows($result_products) . "\n";
    if (mysqli_num_rows($result_products) == 0) {
        echo "No products match the current filters.\n";
    }
} else {
    echo 'Products Query Failed: ' . mysqli_error($conn);
}
echo '</pre>';
echo '</div>';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Plants - Kiyara Plants</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="contecnt/css/shop.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="contecnt/css/style.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="contecnt/css/productSlider.Css" />
    <link rel="stylesheet" href="contecnt/css/header.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="contecnt/css/footer.css?v=<?php echo time(); ?>" />
</head>

<body>
    <?php include 'header.php'; ?>

    <section class="shop-main-section">
        <div class="shop-main">
            <!-- Left side filter section -->
            <div class="shop-filter">
                <div class="shop-filter-border">
                    <h1>Filter</h1>
                </div>

                <!-- Main filter form without submit button for auto-updating fields -->
                <form id="filterForm" action="shop.php" method="GET">
                    <!-- Preserve search query if exists -->
                    <?php if (!empty($search_query)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                    <?php endif; ?>

                <div class="shop-filter-Categories shop-filter-border">
                        <h1>Categories</h1>
                        <div class="shop-filter-Categories-cat">
                            <label>
                                <input type="radio" name="category" value="" 
                                       <?php echo $category_filter == '' ? 'checked' : ''; ?>
                                       onchange="this.form.submit();"> 
                                All Categories
                            </label>
                        </div>
                        <?php foreach ($categories as $category): ?>
                        <div class="shop-filter-Categories-cat">
                            <label>
                                <input type="radio" name="category" value="<?php echo $category['category_id']; ?>" 
                                       <?php echo $category_filter == $category['category_id'] ? 'checked' : ''; ?>
                                       onchange="this.form.submit();"> 
                                <?php echo htmlspecialchars($category['name']); ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>

                <div class="shop-filter-priceRange shop-filter-border">
                    <h1>Price range</h1>
                    <div class="price-range">
                            <input type="number" name="min_price" placeholder="Min" min="<?php echo $db_min_price; ?>" max="<?php echo $db_max_price; ?>" value="<?php echo $min_price; ?>">
                            <input type="number" name="max_price" placeholder="Max" min="<?php echo $db_min_price; ?>" max="<?php echo $db_max_price; ?>" value="<?php echo $max_price; ?>">
                    </div>
                    
                    <button type="submit" class="set-price-range">Set Price Range</button>

                    <h1 class="rating-title">Rating</h1>
                    <div class="shop-filter-Rating">
                        <div class="star-rating">
                            <input type="hidden" name="rating" id="rating-value" value="<?php echo $rating_filter; ?>">
                            <div class="stars">
                                <span class="star <?php echo $rating_filter >= 1 ? 'active' : ''; ?>" data-value="1">
                                    <svg class="fa-star-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg>
                                </span>
                                <span class="star <?php echo $rating_filter >= 2 ? 'active' : ''; ?>" data-value="2">
                                    <svg class="fa-star-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg>
                                </span>
                                <span class="star <?php echo $rating_filter >= 3 ? 'active' : ''; ?>" data-value="3">
                                    <svg class="fa-star-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg>
                                </span>
                                <span class="star <?php echo $rating_filter >= 4 ? 'active' : ''; ?>" data-value="4">
                                    <svg class="fa-star-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg>
                                </span>
                                <span class="star <?php echo $rating_filter >= 5 ? 'active' : ''; ?>" data-value="5">
                                    <svg class="fa-star-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M316.9 18C311.6 7 300.4 0 288.1 0s-23.4 7-28.8 18L195 150.3 51.4 171.5c-12 1.8-22 10.2-25.7 21.7s-.7 24.2 7.9 32.7L137.8 329 113.2 474.7c-2 12 3 24.2 12.9 31.3s23 8 33.8 2.3l128.3-68.5 128.3 68.5c10.8 5.7 23.9 4.9 33.8-2.3s14.9-19.3 12.9-31.3L438.5 329 542.7 225.9c8.6-8.5 11.7-21.2 7.9-32.7s-13.7-19.9-25.7-21.7L381.2 150.3 316.9 18z"/></svg>
                                </span>
                            </div>
                            <div class="rating-text">
                                <?php if ($rating_filter > 0): ?>
                                <span><?php echo $rating_filter; ?> stars and above</span>
                                <a href="#" class="reset-rating">(Reset)</a>
                                <?php else: ?>
                                All ratings
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <a href="shop.php" class="set-price-range clear-filter-btn">Clear All Filters</a>
                </div>
                </form>

                <div class="shop-filter-discount shop-filter-border">
                    <div class="shop-filter-discount-box">
                        <div class="blurry-background"></div>
                        <h1>GET 30% OFF</h1>
                        <p>Buy any 3 cactus and get discount!</p>
                        <a class="btn normal-bt-4" href="">Buy now</a>
                    </div>
                </div>
            </div>

            <!-- Right side products section -->
            <div class="shop-main-prduct-section">
                <div class="shop-main-search">
                    <form action="shop.php" method="GET">
                        <!-- Preserve existing filters when searching -->
                        <?php if (!empty($category_filter)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                        <?php endif; ?>
                        <?php if (isset($_GET['min_price']) && $_GET['min_price'] != $db_min_price): ?>
                        <input type="hidden" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>">
                        <?php endif; ?>
                        <?php if (isset($_GET['max_price']) && $_GET['max_price'] != $db_max_price): ?>
                        <input type="hidden" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>">
                        <?php endif; ?>
                        <?php if (isset($_GET['sort_by']) && $_GET['sort_by'] != 'name_asc'): ?>
                        <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
                        <?php endif; ?>
                        <?php if ($rating_filter > 0): ?>
                        <input type="hidden" name="rating" value="<?php echo htmlspecialchars($rating_filter); ?>">
                        <?php endif; ?>
                        
                        <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search_query); ?>">
                        <button type="submit">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="8" fill="none" stroke="#000" stroke-width="2"/>
                                <path d="M21 21l-6-6" stroke="#000" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </form>
                </div>
                
                <?php if (!empty($search_query)): ?>
                <h1>Search result for <b>"<?php echo htmlspecialchars($search_query); ?>"</b></h1>
                <?php else: ?>
                <h1>All Plants</h1>
                <?php endif; ?>
                
                <div class="shop-main-sort">
                    <h2>Sort</h2>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'name_asc'])); ?>" class="sort-buttons <?php echo $sort_by == 'name_asc' ? 'active' : ''; ?>">Relevance</a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'popular'])); ?>" class="sort-buttons <?php echo $sort_by == 'popular' ? 'active' : ''; ?>">Popular</a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'newest'])); ?>" class="sort-buttons <?php echo $sort_by == 'newest' ? 'active' : ''; ?>">Most New</a>
                    <div class="dropdown">
                        <button class="dropbtn">Price</button>
                        <div class="dropdown-content">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'price_asc'])); ?>" id="lowToHigh">Low to High</a>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['sort_by' => 'price_desc'])); ?>" id="highToLow">High to Low</a>
                        </div>
                    </div>
                </div>

                <div class="shop-main-products">
                    <div class="product-slider-new-arivals">
                        <div class="product-slider">
                            <div class="product-row">
                                <?php
                                if ($result_products && mysqli_num_rows($result_products) > 0) {
                                    while ($product = mysqli_fetch_assoc($result_products)) {
                                        // Determine stock status
                                        $stock_status = "";
                                        if ($product['stock'] <= 0) {
                                            $stock_status = "Out of Stock";
                                        } elseif ($product['stock'] <= 5) {
                                            $stock_status = "Low Stock";
                                        }
                                ?>
                                        <div class="product-slide-box">
                                            <div class="product-slide">
                                                <div class="product-slid-svg-heart-box align-right" data-product-id="<?php echo $product['plant_id']; ?>">
                                                    <svg class="svg-heart svg-icon-big <?php echo in_array($product['plant_id'], $user_wishlist) ? 'active' : ''; ?>" width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M2 9.1371C2 14 6.01943 16.5914 8.96173 18.9109C10 19.7294 11 20.5 12 20.5C13 20.5 14 19.7294 15.0383 18.9109C17.9806 16.5914 22 14 22 9.1371C22 4.27416 16.4998 0.825464 12 5.50063C7.50016 0.825464 2 4.27416 2 9.1371Z" fill="#1C274C" />
                                                    </svg>
                                                </div>
                                        <img class="product-slide-img" src="<?php echo !empty($product['main_image_url']) ? $product['main_image_url'] : $product['img_url']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">

                                        <?php if (!empty($product['origin'])): ?>
                                        <h3 class="p-slid-d-hover"><?php echo htmlspecialchars($product['origin']); ?></h3>
                                        <?php endif; ?>
                                        
                                                <div class="product-slide-name-pot">
                                            <h2 class="p-slid-d-hover"><?php echo htmlspecialchars($product['name']); ?></h2>
                                            <?php if (!empty($product['pot_type'])): ?>
                                            <h3 class="p-slid-d-hover"><?php echo htmlspecialchars($product['pot_type']); ?></h3>
                                            <?php endif; ?>
                                            <?php if (!empty($product['category_name'])): ?>
                                            <span class="p-slid-d-hover category-tag"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                            <?php endif; ?>
                                                </div>

                                        <p class="p-slid-d-hover">Rs.&nbsp;<?php echo number_format($product['price'], 2); ?></p>

                                        <?php if (!empty($stock_status)): ?>
                                        <p class="stock-status"><?php echo $stock_status; ?></p>
                                        <?php endif; ?>
                                            </div>
                                            <div class="prodcut-clik-main">
                                        <?php if ($product['stock'] > 0): ?>
                                        <a href="javascript:void(0)" onclick="addToCart(<?php echo $product['plant_id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>)" class="normal-bt-2 add-to-cart">Add to Cart</a>
                                        <?php else: ?>
                                        <a href="javascript:void(0)" class="normal-bt-2 disabled">Out of Stock</a>
                                        <?php endif; ?>
                                        <a href="product_details.php?id=<?php echo $product['plant_id']; ?>" class="normal-bt-2 product-details">Details</a>
                                            </div>
                                        </div>
                                <?php
                                    }
                                } else {
                                ?>
                                <div class="no-products">
                                    <h3>No products found</h3>
                                    <p>Try adjusting your search or filter criteria</p>
                                    <a href="shop.php" class="normal-bt-2">Clear All Filters</a>
                                </div>
                                <?php
                                }
                                ?>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <div class="shop-bruduct-slider-button-section">
                                <?php if ($current_page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>">
                                    <svg class="shop-bruduct-slider-button slider-button-left" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 101 101">
                                        <path d="M55 67.9c.9.9 2.5.9 3.4 0s.9-2.5 0-3.4l-13.8-13.8 13.7-13.8c.9-.9.9-2.5 0-3.4-.5-.5-1.1-.7-1.7-.7-.6 0-1.2.2-1.7.7L39.5 48c-.5.5-.7 1.1-.7 1.7s.3 1.2.7 1.7L55 67.9z"></path>
                                    </svg>
                                </a>
                                <?php endif; ?>

                                <?php
                                // Show at most 5 page numbers with ellipsis
                                $start_page = max(1, min($current_page - 2, $total_pages - 4));
                                $end_page = min($total_pages, max($current_page + 2, 5));
                                
                                // Always show first page
                                if ($start_page > 1):
                                ?>
                                <a class="page-numbers" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                <?php if ($start_page > 2): ?>
                                <h3>...</h3>
                                <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a class="page-numbers <?php echo $current_page == $i ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                <?php if ($i < $end_page): ?>
                                <h3>&nbsp;</h3>
                                <?php endif; ?>
                                <?php endfor; ?>
                                
                                <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                <h3>...</h3>
                                <?php endif; ?>
                                <h3>&nbsp;</h3>
                                <a class="page-numbers" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
                                <?php endif; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>">
                                    <svg class="shop-bruduct-slider-button slider-button-right" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 101 101">
                                        <path d="M46 33.1c-.9-.9-2.5-.9-3.4 0s-.9 2.5 0 3.4l13.8 13.8-13.7 13.8c-.9.9-.9 2.5 0 3.4.5.5 1.1.7 1.7.7.6 0 1.2-.2 1.7-.7L61.5 52c.5-.5.7-1.1.7-1.7s-.3-1.2-.7-1.7L46 33.1z"></path>
                                    </svg>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
    <button id="debug-toggle" style="position: fixed; bottom: 10px; right: 10px; z-index: 9999; padding: 5px 10px; background: #333; color: #fff; border: none; cursor: pointer;">
        Toggle Debug
    </button>
    <script>
        document.getElementById('debug-toggle').addEventListener('click', function() {
            var debugInfo = document.getElementById('debug-info');
            debugInfo.style.display = debugInfo.style.display === 'none' ? 'block' : 'none';
        });
    </script>
    <?php endif; ?>

    <script>
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
                window.location.href = 'login.php?redirect=shop.php';
            }
        }
        
        // Add to Wishlist function
        function toggleWishlist(plant_id, heartIcon) {
            if (<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>) {
                // Send AJAX request to add item to wishlist
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'add_to_wishlist.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                // Update wishlist UI
                                if (response.inWishlist) {
                                    heartIcon.classList.add('active');
                                } else {
                                    heartIcon.classList.remove('active');
                                }
                                
                                // Update wishlist count if element exists
                                const wishlistCountElement = document.getElementById('wishlist-count');
                                if (wishlistCountElement) {
                                    wishlistCountElement.textContent = response.wishlistCount;
                                }
                                
                                // Optional: Show success message
                                // alert(response.message);
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
            } else {
                // Redirect to login page
                window.location.href = 'login.php?redirect=shop.php';
            }
        }
        
        // Toggle debug info visibility
        function toggleDebug() {
            const debugElement = document.getElementById('debug-info');
            if (debugElement) {
                const currentDisplay = getComputedStyle(debugElement).display;
                debugElement.style.display = currentDisplay === 'none' ? 'block' : 'none';
            }
        }
        
        // Add debug toggle button for admin users
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        window.addEventListener('DOMContentLoaded', function() {
            const shopContainer = document.querySelector('.shop-main');
            if (shopContainer) {
                const debugButton = document.createElement('button');
                debugButton.textContent = 'Toggle Debug Info';
                debugButton.className = 'debug-toggle-btn';
                debugButton.onclick = toggleDebug;
                shopContainer.insertBefore(debugButton, shopContainer.firstChild);
            }
        });
        <?php endif; ?>
    </script>
    
    <!-- Include Product Slider Javascript -->
    <script src="contecnt/js/productSlider.js"></script>
    
    <!-- Fallback script for product buttons if productSlider.js doesn't work -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Setup wishlist heart icon clicks
            const heartIcons = document.querySelectorAll('.product-slid-svg-heart-box');
            
            heartIcons.forEach(function(iconContainer) {
                const heartIcon = iconContainer.querySelector('.svg-heart');
                const productId = iconContainer.dataset.productId;
                
                iconContainer.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Prevent triggering product box click
                    toggleWishlist(productId, heartIcon);
                });
                
                // Make sure wishlist icon is above any blur effect
                iconContainer.style.position = 'relative';
                iconContainer.style.zIndex = '10';
            });
            
            // Handle product boxes hover functionality as fallback
            const productBoxes = document.querySelectorAll('.product-slide-box');
            
            productBoxes.forEach(function(box) {
                const productSlide = box.querySelector('.product-slide');
                const buttonContainer = box.querySelector('.prodcut-clik-main');
                const heartIcon = box.querySelector('.product-slid-svg-heart-box');
                
                // Show buttons on click
                box.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    // Don't blur if clicking on the heart icon
                    if (e.target.closest('.product-slid-svg-heart-box')) {
                        return;
                    }
                    
                    productSlide.classList.toggle('blur');
                    buttonContainer.classList.toggle('visible');
                });
                
                // Prevent click on buttons from toggling the container
                if (buttonContainer) {
                    buttonContainer.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                }
            });
            
            // Hide all product buttons when clicking outside
            document.addEventListener('click', function() {
                document.querySelectorAll('.product-slide.blur').forEach(function(slide) {
                    slide.classList.remove('blur');
                });
                
                document.querySelectorAll('.prodcut-clik-main.visible').forEach(function(container) {
                    container.classList.remove('visible');
                });
            });

            // Star rating system
            const stars = document.querySelectorAll('.star');
            const ratingValue = document.getElementById('rating-value');
            const ratingText = document.querySelector('.rating-text');
            
            // Reset rating functionality
            const resetRatingLink = document.querySelector('.reset-rating');
            if (resetRatingLink) {
                resetRatingLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    ratingValue.value = 0;
                    stars.forEach(function(s) {
                        s.classList.remove('active');
                    });
                    document.getElementById('filterForm').submit();
                });
            }
            
            stars.forEach(function(star) {
                star.addEventListener('click', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    
                    // Set the hidden input value
                    ratingValue.value = value;
                    
                    // Update star styling
                    stars.forEach(function(s) {
                        const starValue = parseInt(s.getAttribute('data-value'));
                        
                        if (starValue <= value) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });
                    
                    // Submit the form automatically
                    document.getElementById('filterForm').submit();
                });
                
                // For better user experience, show visual feedback on hover
                star.addEventListener('mouseover', function() {
                    const value = parseInt(this.getAttribute('data-value'));
                    
                    stars.forEach(function(s) {
                        const starValue = parseInt(s.getAttribute('data-value'));
                        
                        if (starValue <= value) {
                            s.classList.add('hover');
                        } else {
                            s.classList.remove('hover');
                        }
                    });
                });
                
                star.addEventListener('mouseout', function() {
                    stars.forEach(function(s) {
                        s.classList.remove('hover');
                    });
                });
            });
        });
    </script>
</body>

</html>