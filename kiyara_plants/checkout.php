<?php
session_start();
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$success_message = '';
$error_message = '';

// Get user details
$sql_user = "SELECT * FROM customers WHERE customer_id = ?";
$stmt_user = mysqli_prepare($conn, $sql_user);
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$result_user = mysqli_stmt_get_result($stmt_user);
$user = mysqli_fetch_assoc($result_user);

// Get cart items
$sql_cart = "SELECT c.*, p.name, p.pot_type, p.price, p.img_url, p.stock,
            (SELECT pi.image_url FROM plant_images pi WHERE pi.plant_id = p.plant_id AND pi.is_main = 1 LIMIT 1) as main_image_url
            FROM cart c 
            JOIN plants p ON c.plant_id = p.plant_id 
            WHERE c.customer_id = ?";
$stmt_cart = mysqli_prepare($conn, $sql_cart);
mysqli_stmt_bind_param($stmt_cart, "i", $user_id);
mysqli_stmt_execute($stmt_cart);
$result_cart = mysqli_stmt_get_result($stmt_cart);

// Calculate cart total
$cart_total = 0;
$cart_items = [];
if (mysqli_num_rows($result_cart) > 0) {
    while ($item = mysqli_fetch_assoc($result_cart)) {
        $item['subtotal'] = $item['price'] * $item['quantity'];
        $cart_total += $item['subtotal'];
        $cart_items[] = $item;
    }
}

// Process order submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    // Validate shipping address
    if (empty($_POST['shipping_address'])) {
        $error_message = "Shipping address is required.";
    } else {
        $shipping_address = $_POST['shipping_address'];
        
        // Check if cart is empty
        if (count($cart_items) == 0) {
            $error_message = "Your cart is empty. Please add items before checkout.";
        } else {
            // Check if any item is out of stock
            $out_of_stock = false;
            foreach ($cart_items as $item) {
                if ($item['quantity'] > $item['stock']) {
                    $out_of_stock = true;
                    $error_message = "Item '" . $item['name'] . "' has only " . $item['stock'] . " in stock. Please adjust your cart.";
                    break;
                }
            }
            
            if (!$out_of_stock) {
                // Start transaction
                mysqli_begin_transaction($conn);
                
                try {
                    // Create new order
                    $sql_order = "INSERT INTO orders (customer_id, order_date, total_amount, shipping_status) VALUES (?, NOW(), ?, 0)";
                    $stmt_order = mysqli_prepare($conn, $sql_order);
                    mysqli_stmt_bind_param($stmt_order, "id", $user_id, $cart_total);
                    mysqli_stmt_execute($stmt_order);
                    
                    $order_id = mysqli_insert_id($conn);
                    
                    // Insert order details for each cart item
                    $sql_order_details = "INSERT INTO order_details (order_id, plant_id, quantity, subtotal, shipping_status) VALUES (?, ?, ?, ?, 0)";
                    $stmt_order_details = mysqli_prepare($conn, $sql_order_details);
                    
                    // Update plant stock
                    $sql_update_stock = "UPDATE plants SET stock = stock - ? WHERE plant_id = ?";
                    $stmt_update_stock = mysqli_prepare($conn, $sql_update_stock);
                    
                    foreach ($cart_items as $item) {
                        // Add to order details
                        $subtotal = $item['price'] * $item['quantity'];
                        mysqli_stmt_bind_param($stmt_order_details, "iiid", $order_id, $item['plant_id'], $item['quantity'], $subtotal);
                        mysqli_stmt_execute($stmt_order_details);
                        
                        // Update stock
                        mysqli_stmt_bind_param($stmt_update_stock, "ii", $item['quantity'], $item['plant_id']);
                        mysqli_stmt_execute($stmt_update_stock);
                    }
                    
                    // Create initial shipping update
                    $update_description = "Order placed";
                    $sql_shipping_update = "INSERT INTO shipping_updates (order_id, update_date, update_description, updated_by_admin) VALUES (?, NOW(), ?, 0)";
                    $stmt_shipping_update = mysqli_prepare($conn, $sql_shipping_update);
                    mysqli_stmt_bind_param($stmt_shipping_update, "is", $order_id, $update_description);
                    mysqli_stmt_execute($stmt_shipping_update);
                    
                    // Add order to allorders table
                    foreach ($cart_items as $item) {
                        $sql_allorders = "INSERT INTO allorders (customer_id, customer_name, customer_email, plant_id, plant_name, quantity, subtotal, order_date, total_amount, shipping_status, update_date, update_description, updated_by_admin) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, 0, NOW(), ?, 0)";
                        $stmt_allorders = mysqli_prepare($conn, $sql_allorders);
                        $subtotal = $item['price'] * $item['quantity'];
                        mysqli_stmt_bind_param($stmt_allorders, "issiidisd", $user_id, $user['name'], $user['email'], $item['plant_id'], $item['name'], $item['quantity'], $subtotal, $cart_total, $update_description);
                        mysqli_stmt_execute($stmt_allorders);
                    }
                    
                    // Clear cart
                    $sql_clear_cart = "DELETE FROM cart WHERE customer_id = ?";
                    $stmt_clear_cart = mysqli_prepare($conn, $sql_clear_cart);
                    mysqli_stmt_bind_param($stmt_clear_cart, "i", $user_id);
                    mysqli_stmt_execute($stmt_clear_cart);
                    
                    // Commit transaction
                    mysqli_commit($conn);
                    
                    $success_message = "Order placed successfully! Your order ID is #" . $order_id;
                    
                    // Redirect to a thank you page after 3 seconds
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "usershopinghistry.php";
                        }, 3000);
                    </script>';
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    mysqli_rollback($conn);
                    $error_message = "Error processing your order. Please try again. Error: " . $e->getMessage();
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
    <title>Checkout - Kiyara Plants</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="contecnt/css/style.css">
    <link rel="stylesheet" href="contecnt/css/header.css">
    <link rel="stylesheet" href="contecnt/css/footer.css">
    
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .checkout-title {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .checkout-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .checkout-card h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #4caf50;
            outline: none;
        }
        
        .checkout-product {
            display: flex;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .checkout-product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 1rem;
        }
        
        .checkout-product-details {
            flex: 1;
        }
        
        .checkout-product-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .checkout-product-meta {
            color: #777;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .checkout-product-price {
            font-weight: 600;
        }
        
        .checkout-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        
        .checkout-summary-label {
            color: #777;
        }
        
        .checkout-summary-value {
            font-weight: 500;
        }
        
        .checkout-summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #eee;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .btn-checkout {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 1.5rem;
            transition: background-color 0.3s;
        }
        
        .btn-checkout:hover {
            background-color: #388e3c;
        }
        
        .checkout-empty {
            text-align: center;
            padding: 3rem 0;
        }
        
        .checkout-empty h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #555;
        }
        
        .checkout-empty p {
            color: #777;
            margin-bottom: 2rem;
        }
        
        .btn-continue-shopping {
            background-color: #4caf50;
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn-continue-shopping:hover {
            background-color: #388e3c;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="checkout-container">
        <h1 class="checkout-title">Checkout</h1>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (count($cart_items) > 0): ?>
        <form method="POST" action="checkout.php">
            <div class="checkout-grid">
                <div>
                    <div class="checkout-card">
                        <h2>Customer Information</h2>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" class="form-control" id="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly>
                        </div>
                    </div>
                    
                    <div class="checkout-card">
                        <h2>Shipping Address</h2>
                        <div class="form-group">
                            <label for="shipping_address">Address</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="checkout-card">
                        <h2>Order Summary</h2>
                        <?php foreach ($cart_items as $item): ?>
                        <div class="checkout-product">
                            <img src="<?php echo !empty($item['main_image_url']) ? $item['main_image_url'] : $item['img_url']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="checkout-product-image">
                            <div class="checkout-product-details">
                                <h4 class="checkout-product-title"><?php echo htmlspecialchars($item['name']); ?></h4>
                                <div class="checkout-product-meta">Pot: <?php echo htmlspecialchars($item['pot_type']); ?></div>
                                <div class="checkout-product-meta">Quantity: <?php echo $item['quantity']; ?></div>
                                <div class="checkout-product-price">Rs.<?php echo number_format($item['subtotal'], 2); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="checkout-summary">
                            <div class="checkout-summary-row">
                                <div class="checkout-summary-label">Subtotal</div>
                                <div class="checkout-summary-value">Rs.<?php echo number_format($cart_total, 2); ?></div>
                            </div>
                            <div class="checkout-summary-row">
                                <div class="checkout-summary-label">Shipping</div>
                                <div class="checkout-summary-value">Free</div>
                            </div>
                            <div class="checkout-summary-total">
                                <div>Total</div>
                                <div>Rs.<?php echo number_format($cart_total, 2); ?></div>
                            </div>
                            
                            <button type="submit" name="place_order" class="btn-checkout">Place Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <?php else: ?>
        <div class="checkout-card checkout-empty">
            <h3>Your cart is empty</h3>
            <p>Add some plants to your cart before proceeding to checkout.</p>
            <a href="shop.php" class="btn-continue-shopping">Continue Shopping</a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>
