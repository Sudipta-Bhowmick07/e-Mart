<?php
session_start(); // Start the session at the very beginning

// --- AUTHENTICATION AND AUTHORIZATION CHECK ---
// This block ensures only logged-in users with the 'user' role can access this page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php"); // Redirect to login page if not authenticated as a user
    exit(); // Terminate script execution
}

$serverName = "SUDIPTA\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "ClothingStoreDB",
    "TrustServerCertificate" => true
    // Add Uid and PWD if using SQL Server Authentication
    // "Uid" => "your_db_username",
    // "PWD" => "your_db_password"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Check if connection failed before proceeding
if ($conn === false) {
    error_log("Error connecting to database on user_shop.php: " . print_r(sqlsrv_errors(), true));
    // Display a user-friendly error message or redirect
    die("Database connection failed. Please try again later."); // For production, avoid dying directly, use proper error handling
}

// FIX: Handle category filter correctly. If 'category' is not set or empty, show all products.
$categoryFilter = $_GET['category'] ?? ''; // This will be empty string if 'All' is selected or no filter applied.

$products = [];

// Fetch products from the database
$sql = "SELECT ID, ProductName, Price, ImagePath, Category FROM ClothingItems"; // Added Category to select for filter option
$params = [];
if (!empty($categoryFilter)) { // Only add WHERE clause if a specific category is selected
    $sql .= " WHERE Category = ?";
    $params[] = $categoryFilter;
}
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $products[] = $row;
    }
    sqlsrv_free_stmt($stmt);
} else {
    error_log("Error fetching products: " . print_r(sqlsrv_errors(), true));
}
sqlsrv_close($conn); // Close connection after query

// Check for messages from add_to_cart.php (e.g., success or error)
$message = '';
$messageType = ''; // 'success' or 'error'

if (isset($_GET['added_to_cart']) && $_GET['added_to_cart'] == 'true') {
    $message = '✅ Item added to cart successfully!';
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    $error_code = $_GET['error'];
    switch ($error_code) {
        case 'notfound':
            $message = '❌ Error: Product not found.';
            break;
        case 'db_connection':
            $message = '❌ Error: Database connection failed.';
            break;
        case 'db_query':
            $message = '❌ Error: Failed to retrieve product details.';
            break;
        case 'invalid_id':
            $message = '❌ Error: Invalid product ID.';
            break;
        default:
            $message = '❌ An unknown error occurred.';
            break;
    }
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>e-Mart | Clothing Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            padding-top: 60px; /* Reduced padding-top to match navbar height */
            background: linear-gradient(to bottom right, #e0f7fa, #f1fdfd);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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
            box-sizing: border-box; /* Include padding in element's total width and height */
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
            display: flex;
            align-items: center;
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
            /* background-color: #e6f7ff; Consider if active state also has background */
        }
        h1 {
            text-align: center;
            margin-top: 20px;
            color: #343a40;
        }
        .filter-box {
            text-align: center;
            margin: 20px;
        }
        .filter-box select {
            padding: 8px 12px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc; /* Added border */
            background-color: #fff;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); /* Responsive grid */
            gap: 20px;
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1; /* Allows grid to take available space and push footer down */
        }
        .card {
            background: #fffde7;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            text-align: center;
            height: 380px; /* Fixed height for consistent card size */
            display: flex;
            flex-direction: column;
            justify-content: space-between; /* Distribute content vertically */
        }
        .card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .price {
            color: green;
            font-weight: bold;
            margin-top: 8px; /* Spacing for price */
            margin-bottom: 8px;
        }
        .btn { /* Reverted to .btn class */
            background: #343a40;
            color: white;
            padding: 8px 15px; /* Increased padding */
            border: none;
            border-radius: 5px;
            margin-top: 10px;
            cursor: pointer;
            text-decoration: none; /* Ensure it looks like a button */
            display: inline-block; /* For proper padding and alignment */
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background: #23272b;
        }
        .footer {
            background: #0074cc;
            color: white;
            padding: 15px 20px 5px;
            font-size: 14px;
            margin-top: auto; /* Pushes footer to the bottom */
            box-sizing: border-box; /* Include padding in element's total width and height */
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
        /* Styles for the PHP-displayed message */
        .php-message {
            text-align: center;
            padding: 10px;
            margin: 10px auto;
            border-radius: 5px;
            max-width: 600px;
            font-weight: bold;
        }
        .php-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .php-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">e-Mart</div>
    <ul>
        <li><a href="user_home.php"><i class="fas fa-home"></i>Home</a></li>
        <li class="active"><a href="user_shop.php"><i class="fas fa-store"></i>Shop</a></li>
        <li><a href="user_cart.php"><i class="fas fa-shopping-cart"></i>Cart</a></li>
        <li><a href="user_orders.php"><i class="fas fa-box"></i>Orders</a></li>
        <!-- Logout link, always available for logged-in users -->
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<h1>🛍️ Welcome to Our Clothing Store</h1>

<?php if (!empty($message)): ?>
    <div class="php-message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="filter-box">
    <form method="GET" action="">
        <label for="category">Filter by Category:</label>
        <select name="category" id="category" onchange="this.form.submit()">
            <!-- FIXED: The "All" option now sends an empty value -->
            <option value="" <?php if(empty($categoryFilter)) echo 'selected'; ?>>-- All --</option>
            <option value="Pant" <?php if ($categoryFilter == 'Pant') echo 'selected'; ?>>Pant</option>
            <option value="Shirt" <?php if ($categoryFilter == 'Shirt') echo 'selected'; ?>>Shirt</option>
            <option value="t-Shirt" <?php if ($categoryFilter == 't-Shirt') echo 'selected'; ?>>t-Shirt</option>
            <option value="Hoodie" <?php if ($categoryFilter == 'Hoodie') echo 'selected'; ?>>Hoodie</option>
            <option value="Shorts" <?php if ($categoryFilter == 'Shorts') echo 'selected'; ?>>Shorts</option>
            <!-- Add other categories if you have them in your database -->
        </select>
    </form>
</div>

<div class="product-grid">
    <?php if (count($products) > 0): ?>
        <?php foreach ($products as $product): ?>
            <div class="card">
                <?php if (!empty($product['ImagePath'])): ?>
                    <img src="<?php echo htmlspecialchars($product['ImagePath']); ?>" alt="product">
                <?php else: ?>
                    <img src="images/placeholder.png" alt="No image">
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($product['ProductName']); ?></h3>
                <p class="price">₹<?php echo htmlspecialchars($product['Price']); ?></p>
                <a href="add_to_cart.php?id=<?= htmlspecialchars($product['ID']) ?>" class="btn">Add to Cart</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="text-align:center; width: 100%;">No products found in this category.</p>
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
