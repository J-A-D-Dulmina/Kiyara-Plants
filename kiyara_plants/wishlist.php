<?php
session_start();
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=wishlist.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = $error_message = '';

// Handle remove from wishlist
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $plant_id = (int)$_GET['remove'];
    
    $sql_remove = "DELETE FROM wishlist WHERE user_id = ? AND plant_id = ?";
    $stmt_remove = mysqli_prepare($conn, $sql_remove);
    mysqli_stmt_bind_param($stmt_remove, "ii", $user_id, $plant_id);
    
    if (mysqli_stmt_execute($stmt_remove)) {
        $success_message = "Item removed from wishlist";
    } else {
        $error_message = "Error removing item from wishlist";
    }
}

// Get user's wishlist items
$sql_wishlist = "SELECT w.*, p.name, p.price, p.stock, p.status, 
                (SELECT pi.image_url FROM plant_images pi WHERE pi.plant_id = p.plant_id AND pi.is_main = 1 LIMIT 1) as main_image_url,
                p.img_url,
                GROUP_CONCAT(c.name SEPARATOR ', ') as category_name
                FROM wishlist w
                JOIN plants p ON w.plant_id = p.plant_id
                LEFT JOIN plant_category pc ON p.plant_id = pc.plant_id
                LEFT JOIN categories c ON pc.category_id = c.category_id
                WHERE w.user_id = ? AND p.status = 1
                GROUP BY w.wishlist_id
                ORDER BY w.added_at DESC";

$stmt_wishlist = mysqli_prepare($conn, $sql_wishlist);
mysqli_stmt_bind_param($stmt_wishlist, "i", $user_id);
mysqli_stmt_execute($stmt_wishlist);
$result_wishlist = mysqli_stmt_get_result($stmt_wishlist);
$wishlist_items = mysqli_fetch_all($result_wishlist, MYSQLI_ASSOC);
$wishlist_count = count($wishlist_items);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - Kiyara Plants</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="contecnt/css/style.css">
    <link rel="stylesheet" href="contecnt/css/header.css">
    <link rel="stylesheet" href="contecnt/css/footer.css">
    
    <style>
        .wishlist-container {
            max-width: 1200px;
            margin: 80px auto;
            padding: 0 20px;
        }
        
        .wishlist-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .wishlist-title {
            font-size: 28px;
            color: #333;
        }
        
        .wishlist-count {
            color: #666;
            font-size: 16px;
        }
        
        .empty-wishlist {
            text-align: center;
            padding: 60px 0;
        }
        
        .empty-wishlist h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #666;
        }
        
        .shop-now-btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .shop-now-btn:hover {
            background-color: #388E3C;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .wishlist-items {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .wishlist-item {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.3s;
            position: relative;
            background-color: #fff;
        }
        
        .wishlist-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }
        
        .wishlist-item-img-container {
            position: relative;
            width: 100%;
            height: 250px;
            overflow: hidden;
        }
        
        .wishlist-item-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background-color: #f9f9f9;
            transition: all 0.3s;
        }
        
        .remove-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            background-color: #f44336;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 10;
            text-decoration: none;
        }
        
        .remove-btn:hover {
            background-color: #d32f2f;
            transform: scale(1.1);
        }
        
        .wishlist-item-details {
            padding: 15px;
        }
        
        .wishlist-item-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .wishlist-item-category {
            color: #888;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .wishlist-item-price {
            font-size: 18px;
            font-weight: 600;
            color: #4CAF50;
            margin-bottom: 15px;
        }
        
        .wishlist-item-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-add-cart,
        .btn-view {
            display: inline-block;
            text-decoration: none;
            padding: 1.1rem 2rem;
            font-size: 1.2rem;
            font-weight: 400;
            border-radius: 5px;
            outline: none;
            transition: ease-out 0.5s;
            text-align: center;
            flex: 1;
        }
        
        .btn-add-cart {
            background-color: #4fc4c3;
            color: #fff;
        }
        
        .btn-add-cart:hover {
            cursor: pointer;
            box-shadow: inset 20rem 0 0 0 #287b7b;
            background-color: #287b7b;
            color: #fff;
        }
        
        .btn-view {
            background-color: transparent;
            color: #2196F3;
            border: 1px solid #2196F3;
        }
        
        .btn-view:hover {
            background-color: #2196F3;
            color: white;
        }
        
        .stock-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .in-stock {
            background-color: #E8F5E9;
            color: #388E3C;
        }
        
        .out-stock {
            background-color: #FFEBEE;
            color: #D32F2F;
        }
        
        .low-stock {
            background-color: #FFF8E1;
            color: #FFA000;
        }
        
        @media (max-width: 768px) {
            .wishlist-items {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .wishlist-item-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="wishlist-container">
        <div class="wishlist-header">
            <h1 class="wishlist-title">My Wishlist</h1>
            <span class="wishlist-count"><?php echo $wishlist_count; ?> item<?php echo $wishlist_count != 1 ? 's' : ''; ?></span>
        </div>
        
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (empty($wishlist_items)): ?>
        <div class="empty-wishlist">
            <h2>Your wishlist is empty</h2>
            <p>Add items to your wishlist by clicking the heart icon on product pages.</p>
            <a href="shop.php" class="shop-now-btn">Shop Now</a>
        </div>
        <?php else: ?>
        <div class="wishlist-items">
            <?php foreach ($wishlist_items as $item): ?>
            <div class="wishlist-item">
                <div class="wishlist-item-img-container">
                    <img src="<?php echo !empty($item['main_image_url']) ? $item['main_image_url'] : $item['img_url']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="wishlist-item-img">
                    <a href="wishlist.php?remove=<?php echo $item['plant_id']; ?>" class="remove-btn" onclick="return confirm('Are you sure you want to remove this item from your wishlist?')">X</a>
                </div>
                <div class="wishlist-item-details">
                    <h3 class="wishlist-item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                    
                    <?php if (!empty($item['category_name'])): ?>
                    <div class="wishlist-item-category"><?php echo htmlspecialchars($item['category_name']); ?></div>
                    <?php endif; ?>
                    
                    <?php
                    // Determine stock status
                    if ($item['stock'] <= 0) {
                        echo '<div class="stock-status out-stock">Out of Stock</div>';
                    } elseif ($item['stock'] <= 5) {
                        echo '<div class="stock-status low-stock">Low Stock</div>';
                    } else {
                        echo '<div class="stock-status in-stock">In Stock</div>';
                    }
                    ?>
                    
                    <div class="wishlist-item-price">Rs.<?php echo number_format($item['price'], 2); ?></div>
                    
                    <div class="wishlist-item-actions">
                        <?php if ($item['stock'] > 0): ?>
                        <button class="btn-add-cart" onclick="addToCart(<?php echo $item['plant_id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>', <?php echo $item['price']; ?>)">
                            Add to Cart
                        </button>
                        <?php else: ?>
                        <button class="btn-add-cart" disabled style="background-color: #ccc; cursor: not-allowed;">
                            Out of Stock
                        </button>
                        <?php endif; ?>
                        
                        <a href="product_details.php?id=<?php echo $item['plant_id']; ?>" class="btn-view">View</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <script>
        // Add to Cart function
        function addToCart(plant_id, name, price) {
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
        }
    </script>
</body>
</html> 