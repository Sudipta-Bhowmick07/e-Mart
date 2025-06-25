<?php
session_start(); // Start the session at the very beginning

// --- AUTHENTICATION AND AUTHORIZATION CHECK ---
// This block ensures only logged-in users with the 'user' role can access this page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php"); // Redirect to login page if not authenticated as a user
    exit(); // Terminate script execution
}

// Get logged-in user's ID
$loggedInUserId = $_SESSION['user_id'];

// Database connection details
$serverName = "SUDIPTA\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "ClothingStoreDB",
    "TrustServerCertificate" => true
    // Add Uid and PWD if using SQL Server Authentication
    // "Uid" => "your_db_username",
    // "PWD" => "your_db_password"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    error_log("Database connection failed on user_cart.php: " . print_r(sqlsrv_errors(), true));
    // Display a critical error message or handle gracefully
    die("Database connection failed. Please try again later.");
}

// --- PERSISTENT CART LOGIC: Handle removal via database ---
if (isset($_GET['remove_index']) && is_numeric($_GET['remove_index']) && isset($_GET['confirmed']) && $_GET['confirmed'] == 'true') {
    $removeIndex = (int)$_GET['remove_index'];

    // To remove from database, we need the actual UserCartItem.ID, not just array index.
    // We'll rely on the JavaScript to redirect directly to user_cart.php after successful DB removal.
    // For now, let's assume the remove_index might be problematic if items shifted.
    // A more robust approach would be to pass the actual UserCartItems.ID to confirm_remove.php
    // and then to here. For now, we'll try to find the item based on its presence in the UI data.

    // If you pass UserCartItems.ID from confirm_remove.php, this would be cleaner:
    // $userCartItemId = filter_var($_GET['user_cart_item_id'], FILTER_VALIDATE_INT);
    // if ($userCartItemId !== false) { ... DELETE FROM dbo.UserCartItems WHERE ID = ? ... }
    
    // For now, let's keep the existing $_SESSION['cart'] index based removal, but it's less robust.
    // If you want to strictly rely on DB for removal, the flow from confirm_remove.php needs adjustment.
    // The safest is to DELETE directly from database based on UserCartItem ID passed from confirm_remove.php
    // For now, I'll update the display logic to always fetch from DB.
}


$cartItems = [];
$totalCartPrice = 0;

// --- FETCH CART ITEMS FROM DATABASE ---
// Joining with ClothingItems to get product details like ProductName, Price, ImagePath
$sql_fetch_cart = "SELECT
                        uci.ID as UserCartItemID, -- Important: Get the ID of the cart item itself
                        uci.ProductID,
                        uci.Quantity,
                        uci.Size,
                        ci.ProductName,
                        ci.Price,
                        ci.ImagePath
                    FROM
                        dbo.UserCartItems uci
                    JOIN
                        ClothingItems ci ON uci.ProductID = ci.ID
                    WHERE
                        uci.UserID = ?";
$params_fetch_cart = array($loggedInUserId);
$stmt_fetch_cart = sqlsrv_query($conn, $sql_fetch_cart, $params_fetch_cart);

if ($stmt_fetch_cart === false) {
    error_log("Database error fetching user cart items: " . print_r(sqlsrv_errors(), true));
    // Cart will be empty, error message can be displayed in HTML
} else {
    while ($row = sqlsrv_fetch_array($stmt_fetch_cart, SQLSRV_FETCH_ASSOC)) {
        // Store product image path, providing fallback
        $row['ImagePath'] = $row['ImagePath'] ?? 'images/placeholder.png';
        $cartItems[] = $row;
        $totalCartPrice += ($row['Price'] * $row['Quantity']);
    }
    sqlsrv_free_stmt($stmt_fetch_cart);
}

sqlsrv_close($conn); // Close DB connection after fetching data

// Handle messages from redirects
$message = '';
$messageType = '';

if (isset($_GET['order_placed']) && $_GET['order_placed'] == 'true') {
    $message = '✅ Order placed successfully!';
    $messageType = 'success';
} elseif (isset($_GET['order_error'])) {
    $message = '❌ Error placing order: ' . htmlspecialchars($_GET['order_error']);
    $messageType = 'error';
} elseif (isset($_GET['removed']) && $_GET['removed'] == 'true') {
    $message = '✅ Item removed from cart!';
    $messageType = 'success';
} elseif (isset($_GET['removed']) && $_GET['removed'] == 'false' && isset($_GET['error']) && $_GET['error'] == 'item_not_found') {
    $message = '❌ Error: Item not found in cart for removal.';
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>e-Mart | Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding-top: 60px; /* Consistent with other pages for fixed navbar */
            background: linear-gradient(to bottom right, #e0f7fa, #f1fdfd); /* Consistent background */
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* Ensures the body always takes at least the full viewport height */
        }
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: #fff;
            border-bottom: 2px solid #f2f2f2;
            padding: 15px 50px;
            display: flex;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            gap: 50px;
            box-sizing: border-box;
        }
        .navbar .logo {
            font-size: 24px;
            font-weight: bold;
            color: #0074cc;
        }
        .navbar ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 25px;
            margin-left: auto; /* Pushes the menu items to the right */
        }
        .navbar ul li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            display: flex; /* For icon alignment */
            align-items: center; /* For icon alignment */
            padding: 5px 10px; /* Added padding for better click area */
            border-radius: 5px; /* Subtle rounding */
            transition: color 0.3s, background-color 0.3s;
        }
        .navbar ul li a:hover {
            color: #0074cc;
            background-color: #e6f7ff; /* Light background on hover */
        }
        .navbar ul li a i {
            margin-right: 6px;
        }
        .navbar ul li.active a {
            color: #0074cc;
            font-weight: bold;
        }

        /* Main content container */
        .main-content-wrapper { /* New wrapper for the dynamic content */
            flex: 1; /* This pushes the footer down by taking up available space */
            padding-bottom: 20px; /* Optional: Add some padding to the bottom of the main content before the footer */
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            margin-top: 20px; /* Add some top margin to separate from navbar */
        }
        .cart-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 10px;
            box-sizing: border-box;
        }
        .card {
            display: flex;
            align-items: center;
            background: #fffde7;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .card img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
        }
        .card h3 {
            margin: 0 0 8px;
        }
        .card .price {
            color: green;
            font-weight: bold;
        }
        .actions {
            margin-left: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .actions a, .actions button {
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 5px;
            text-align: center;
            color: #fff;
            border: none;
            cursor: pointer;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        .buy-btn {
            background-color: #007bff;
        }
        .buy-btn:hover {
            background-color: #0056b3;
        }
        .remove-btn {
            background-color: #dc3545;
        }
        .remove-btn:hover {
            background-color: #c82333;
        }
        .message {
            text-align: center;
            padding: 10px;
            margin: 10px auto;
            border-radius: 5px;
            max-width: 600px;
            font-weight: bold;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .cart-total-section {
            max-width: 1000px;
            margin: 20px auto;
            padding: 15px;
            background: #fffde7;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: right;
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            box-sizing: border-box;
        }
        /* Footer styles - no change needed here if body and main-content-wrapper are correct */
        .footer {
            background: #0074cc;
            color: white;
            padding: 15px 20px 5px;
            margin-top: auto; /* Pushes footer to the bottom */
            font-size: 14px;
            box-sizing: border-box;
            width: 100%;
        }
        .footer-content {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            max-width: 1000px;
            margin: auto;
        }
        .footer-content div {
            margin-bottom: 8px;
        }
        .footer h3, .footer h4 {
            margin: 0 0 5px;
            font-size: 16px;
        }
        .footer p {
            margin: 0;
            line-height: 1.4;
        }
        .footer-bottom {
            text-align: center;
            margin-top: 8px;
            font-size: 13px;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 6px;
        }
    </style>
</head>
<body>
<div class="navbar">
    <div class="logo">e-Mart</div>
    <ul>
        <li><a href="user_home.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="user_shop.php"><i class="fas fa-store"></i> Shop</a></li>
        <li class="active"><a href="user_cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
        <li><a href="user_orders.php"><i class="fas fa-box"></i> Orders</a></li>
        <!-- Logout link, always available for logged-in users -->
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<div class="main-content-wrapper">
    <h1>🛒 Your Shopping Cart</h1>
    <?php if (!empty($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="cart-container">
        <?php if (empty($cartItems)): ?>
            <p style="text-align:center; color: #888;">Your cart is empty.</p>
        <?php else: ?>
            <?php foreach ($cartItems as $item): ?>
                <div class="card">
                    <img src="<?= htmlspecialchars($item['ImagePath']) ?>" alt="<?= htmlspecialchars($item['ProductName']) ?>">
                    <div>
                        <h3><?= htmlspecialchars($item['ProductName']) ?></h3>
                        <p class="price">₹<?= htmlspecialchars($item['Price']) ?> × <?= htmlspecialchars($item['Quantity']) ?> = ₹<?= htmlspecialchars($item['Price'] * $item['Quantity']) ?></p>
                        <p>Size: <?= htmlspecialchars($item['Size'] ?? 'N/A') ?></p>
                    </div>
                    <div class="actions">
                        <!-- Pass the UserCartItemID to select_size.php for specific item purchase -->
                        <a href="select_size.php?product_id=<?= htmlspecialchars($item['ProductID']) ?>&user_cart_item_id=<?= htmlspecialchars($item['UserCartItemID']) ?>" class="buy-btn">Buy Now</a>
                        <!-- Pass the UserCartItemID to confirm_remove.php for specific item removal -->
                        <a href="confirm_remove.php?user_cart_item_id=<?= htmlspecialchars($item['UserCartItemID']) ?>" class="remove-btn">Remove</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($cartItems)): ?>
    <div class="cart-total-section">
        Total Cart Price: ₹<?= htmlspecialchars($totalCartPrice) ?>
    </div>
    <?php endif; ?>
</div>

<footer class="footer">
    <div class="footer-content">
        <div>
            <h3>e-Mart</h3>
            <p>123 Fashion Street,<br>Puri, Odisha - 752001</p>
        </div>
        <div>
            <h4>Contact Us</h4>
            <p>Email: support@emart.com<br>Phone: +91 98765 43210</p>
        </div>
    </div>
    <div class="footer-bottom">
        © <?php echo date("Y"); ?> e-Mart. All rights reserved.
    </div>
</footer>

</body>
</html>
