<?php
session_start();
include 'db_connection.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart']);
    exit;
}

// Check if product_id and quantity are provided
if ((!isset($_POST['product_id']) && !isset($_POST['plant_id'])) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Get product_id and quantity
$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : $_POST['plant_id'];
$quantity = $_POST['quantity'];

// Validate quantity
if (!is_numeric($quantity) || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
    exit;
}

// Check if product exists and has enough stock
$sql_product = "SELECT * FROM plants WHERE plant_id = ? AND status = 1";
$stmt_product = mysqli_prepare($conn, $sql_product);
mysqli_stmt_bind_param($stmt_product, "i", $product_id);
mysqli_stmt_execute($stmt_product);
$result_product = mysqli_stmt_get_result($stmt_product);

if (mysqli_num_rows($result_product) == 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Product not found'
    ]);
    exit();
}

$product = mysqli_fetch_assoc($result_product);
if ($product['stock'] < $quantity) {
    echo json_encode([
        'success' => false,
        'message' => 'Not enough stock available. Only ' . $product['stock'] . ' items available.'
    ]);
    exit();
}

// Check if product is already in cart
$sql_check_cart = "SELECT * FROM cart WHERE customer_id = ? AND plant_id = ?";
$stmt_check_cart = mysqli_prepare($conn, $sql_check_cart);
mysqli_stmt_bind_param($stmt_check_cart, "ii", $_SESSION['user_id'], $product_id);
mysqli_stmt_execute($stmt_check_cart);
$result_check_cart = mysqli_stmt_get_result($stmt_check_cart);

if (mysqli_num_rows($result_check_cart) > 0) {
    // Update quantity if product already in cart
    $cart_item = mysqli_fetch_assoc($result_check_cart);
    $new_quantity = $cart_item['quantity'] + $quantity;
    
    // Check if new quantity exceeds stock
    if ($new_quantity > $product['stock']) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot add more items. Cart would exceed available stock.'
        ]);
        exit();
    }
    
    $sql_update_cart = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
    $stmt_update_cart = mysqli_prepare($conn, $sql_update_cart);
    mysqli_stmt_bind_param($stmt_update_cart, "ii", $new_quantity, $cart_item['cart_id']);
    
    if (mysqli_stmt_execute($stmt_update_cart)) {
        $cart_count = getCartCount($conn, $_SESSION['user_id']);
        echo json_encode([
            'success' => true,
            'message' => 'Product quantity updated in cart',
            'cartCount' => $cart_count
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating cart: ' . mysqli_error($conn)
        ]);
    }
} else {
    // Add new product to cart
    $sql_add_cart = "INSERT INTO cart (customer_id, plant_id, quantity) VALUES (?, ?, ?)";
    $stmt_add_cart = mysqli_prepare($conn, $sql_add_cart);
    mysqli_stmt_bind_param($stmt_add_cart, "iii", $_SESSION['user_id'], $product_id, $quantity);
    
    if (mysqli_stmt_execute($stmt_add_cart)) {
        $cart_count = getCartCount($conn, $_SESSION['user_id']);
        echo json_encode([
            'success' => true,
            'message' => 'Product added to cart',
            'cartCount' => $cart_count
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error adding to cart: ' . mysqli_error($conn)
        ]);
    }
}

// Function to get cart count
function getCartCount($conn, $user_id) {
    $sql_count = "SELECT SUM(quantity) as count FROM cart WHERE customer_id = ?";
    $stmt_count = mysqli_prepare($conn, $sql_count);
    mysqli_stmt_bind_param($stmt_count, "i", $user_id);
    mysqli_stmt_execute($stmt_count);
    $result_count = mysqli_stmt_get_result($stmt_count);
    $row = mysqli_fetch_assoc($result_count);
    return $row['count'] ? $row['count'] : 0;
}
?> 