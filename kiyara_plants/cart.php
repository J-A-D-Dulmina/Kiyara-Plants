<?php
session_start();
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php?redirect=cart.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$success_message = '';
$error_message = '';

// Handle remove from cart
if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    $cart_id = $_GET['remove'];
    
    // Verify this cart item belongs to the user
    $sql_verify = "SELECT * FROM cart WHERE cart_id = ? AND customer_id = ?";
    $stmt_verify = mysqli_prepare($conn, $sql_verify);
    if ($stmt_verify) {
        mysqli_stmt_bind_param($stmt_verify, "ii", $cart_id, $user_id);
        mysqli_stmt_execute($stmt_verify);
        $result_verify = mysqli_stmt_get_result($stmt_verify);
        
        if (mysqli_num_rows($result_verify) > 0) {
            $sql_remove = "DELETE FROM cart WHERE cart_id = ?";
            $stmt_remove = mysqli_prepare($conn, $sql_remove);
            if ($stmt_remove) {
                mysqli_stmt_bind_param($stmt_remove, "i", $cart_id);
                
                if (mysqli_stmt_execute($stmt_remove)) {
                    $success_message = "Item removed from cart.";
                } else {
                    $error_message = "Error removing item from cart.";
                }
            } else {
                $error_message = "Error preparing statement: " . mysqli_error($conn);
            }
        }
    } else {
        $error_message = "Error preparing statement: " . mysqli_error($conn);
    }
}

// Handle update quantity
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $cart_id => $quantity) {
        // Validate quantity
        $quantity = (int)$quantity;
        if ($quantity <= 0) {
            continue; // Skip zero or negative quantities (or handle removal)
        }
        
        // Verify this cart item belongs to the user
        $sql_verify = "SELECT c.*, p.stock, p.name FROM cart c 
                      JOIN plants p ON c.plant_id = p.plant_id 
                      WHERE c.cart_id = ? AND c.customer_id = ?";
        $stmt_verify = mysqli_prepare($conn, $sql_verify);
        if ($stmt_verify) {
            mysqli_stmt_bind_param($stmt_verify, "ii", $cart_id, $user_id);
            mysqli_stmt_execute($stmt_verify);
            $result_verify = mysqli_stmt_get_result($stmt_verify);
            
            if (mysqli_num_rows($result_verify) > 0) {
                $cart_item = mysqli_fetch_assoc($result_verify);
                
                // Check if requested quantity exceeds stock
                if ($quantity > $cart_item['stock']) {
                    $error_message = "Quantity for " . $cart_item['name'] . " exceeds available stock.";
                    continue;
                }
                
                // Update quantity
                $sql_update = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
                $stmt_update = mysqli_prepare($conn, $sql_update);
                if ($stmt_update) {
                    mysqli_stmt_bind_param($stmt_update, "ii", $quantity, $cart_id);
                    
                    if (!mysqli_stmt_execute($stmt_update)) {
                        $error_message = "Error updating cart.";
                    }
                } else {
                    $error_message = "Error preparing statement: " . mysqli_error($conn);
                }
            }
        } else {
            $error_message = "Error preparing statement: " . mysqli_error($conn);
        }
    }
    
    if (empty($error_message)) {
        $success_message = "Cart updated successfully.";
    }
}

// Get cart items
$sql_cart = "SELECT c.cart_id, c.quantity, p.plant_id, p.name, p.price, p.img_url, p.stock,
            (SELECT pi.image_url FROM plant_images pi WHERE pi.plant_id = p.plant_id AND pi.is_main = 1 LIMIT 1) as main_image_url
            FROM cart c
            JOIN plants p ON c.plant_id = p.plant_id
            WHERE c.customer_id = ?
            ORDER BY c.cart_id DESC";
$stmt_cart = mysqli_prepare($conn, $sql_cart);
if ($stmt_cart) {
    mysqli_stmt_bind_param($stmt_cart, "i", $user_id);
    mysqli_stmt_execute($stmt_cart);
    $result_cart = mysqli_stmt_get_result($stmt_cart);
    
    // Calculate cart total
    $cart_total = 0;
    $cart_items = [];
    
    if ($result_cart) {
        while ($item = mysqli_fetch_assoc($result_cart)) {
            $item['subtotal'] = $item['price'] * $item['quantity'];
            $cart_total += $item['subtotal'];
            $cart_items[] = $item;
        }
        
        // Reset result pointer if there are items
        if (!empty($cart_items)) {
            mysqli_data_seek($result_cart, 0);
        }
    }
} else {
    $error_message = "Error preparing statement: " . mysqli_error($conn);
    $cart_items = [];
    $cart_total = 0;
    $result_cart = false;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Kiyara Plants</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="contecnt/css/style.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="contecnt/css/header.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="contecnt/css/footer.css?v=<?php echo time(); ?>" />
    <link rel="stylesheet" href="contecnt/css/cart.css?v=<?php echo time(); ?>" />
    
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="cart-container">
        <div class="cart-header">
            <h1>Your Shopping Cart</h1>
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
        
        <?php if (empty($cart_items)): ?>
        <div class="cart-empty">
            <p>Your cart is empty. Start adding some plants!</p>
            <a href="shop.php" class="btn">Shop Now</a>
        </div>
        <?php else: ?>
        
        <div class="cart-content">
            <div class="cart-items">
                <form method="POST" action="cart.php">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="cart-product">
                                        <img src="<?php echo !empty($item['main_image_url']) ? $item['main_image_url'] : $item['img_url']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-product-image">
                                        <div class="cart-product-details">
                                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                            <?php if ($item['stock'] <= 5): ?>
                                            <small class="text-warning">Only <?php echo $item['stock']; ?> left in stock</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="cart-price">Rs.<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <div class="quantity-control">
                                        <button type="button" class="quantity-btn" onclick="decreaseQuantity(<?php echo $item['cart_id']; ?>)">-</button>
                                        <input type="number" name="quantity[<?php echo $item['cart_id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" class="quantity-input" id="quantity-<?php echo $item['cart_id']; ?>" onchange="updateCartItem(<?php echo $item['cart_id']; ?>, this.value)">
                                        <button type="button" class="quantity-btn" onclick="increaseQuantity(<?php echo $item['cart_id']; ?>, <?php echo $item['stock']; ?>)">+</button>
                                    </div>
                                </td>
                                <td class="cart-subtotal">Rs.<?php echo number_format($item['subtotal'], 2); ?></td>
                                <td>
                                    <a href="cart.php?remove=<?php echo $item['cart_id']; ?>" class="cart-remove" onclick="return confirm('Are you sure you want to remove this item?')">
                                        <span class="remove-icon">×</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="cart-actions">
                        <a href="shop.php" class="continue-shopping">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                            </svg>
                            Continue Shopping
                        </a>
                    </div>
                </form>
            </div>
            
            <div class="cart-summary">
                <div class="cart-summary-box">
                    <h2 class="summary-header">Order Summary</h2>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>Rs.<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>Free</span>
                    </div>
                    
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span>Rs.<?php echo number_format($cart_total, 2); ?></span>
                    </div>
                    
                    <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Font Awesome for icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <script>
        function decreaseQuantity(cartId) {
            const input = document.getElementById('quantity-' + cartId);
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
                updateCartItem(cartId, currentValue - 1);
            }
        }
        
        function increaseQuantity(cartId, maxStock) {
            const input = document.getElementById('quantity-' + cartId);
            const currentValue = parseInt(input.value);
            if (currentValue < maxStock) {
                input.value = currentValue + 1;
                updateCartItem(cartId, currentValue + 1);
            }
        }

        function updateCartItem(cartId, quantity) {
            // Create form data
            const formData = new FormData();
            formData.append('update_cart', 'true');
            formData.append(`quantity[${cartId}]`, quantity);
            
            // Show loading indicator
            const subtotalCell = document.querySelector(`#quantity-${cartId}`).closest('tr').querySelector('.cart-subtotal');
            const originalContent = subtotalCell.innerHTML;
            subtotalCell.innerHTML = '<div class="loading-spinner"></div>';
            
            // Send AJAX request
            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Extract just the updated cart content from the response
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                
                // Update subtotal for this item
                const updatedRow = doc.querySelector(`#quantity-${cartId}`).closest('tr');
                const updatedSubtotal = updatedRow.querySelector('.cart-subtotal').innerHTML;
                subtotalCell.innerHTML = updatedSubtotal;
                
                // Update order summary total
                const updatedSummary = doc.querySelector('.summary-total').innerHTML;
                document.querySelector('.summary-total').innerHTML = updatedSummary;
                
                // Check if there are any alerts to display
                const alerts = doc.querySelectorAll('.alert');
                if (alerts.length > 0) {
                    // Find existing alerts in the current page
                    const existingAlerts = document.querySelectorAll('.alert');
                    existingAlerts.forEach(alert => alert.remove());
                    
                    // Insert new alerts
                    const cartHeader = document.querySelector('.cart-header');
                    alerts.forEach(alert => {
                        cartHeader.insertAdjacentHTML('afterend', alert.outerHTML);
                    });
                }
            })
            .catch(error => {
                console.error('Error updating cart:', error);
                subtotalCell.innerHTML = originalContent;
                
                // Show error message
                const cartHeader = document.querySelector('.cart-header');
                const errorAlert = document.createElement('div');
                errorAlert.className = 'alert alert-danger';
                errorAlert.textContent = 'Error updating cart. Please try again.';
                cartHeader.insertAdjacentElement('afterend', errorAlert);
                
                // Remove error message after 3 seconds
                setTimeout(() => {
                    errorAlert.remove();
                }, 3000);
            });
        }
    </script>
</body>

</html>