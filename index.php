<?php
session_start(); // Start the session at the very beginning

// --- AUTHENTICATION AND AUTHORIZATION CHECK ---
// This block ensures only logged-in users with the 'admin' role can access this page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect to login page if not authenticated as an admin
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
    error_log("Error connecting to database on admin_shop_view.php: " . print_r(sqlsrv_errors(), true));
    die("Database connection failed. Please try again later.");
}

// Handle category filter correctly. If 'category' is not set or empty, show all products.
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

// Admin specific messages (e.g., product added/edited/deleted)
$message = '';
$messageType = '';

// You can add logic here to display messages related to admin actions, e.g.:
// if (isset($_GET['product_updated']) && $_GET['product_updated'] == 'true') {
//     $message = '✅ Product updated successfully!';
//     $messageType = 'success';
// } elseif (isset($_GET['product_deleted']) && $_GET['product_deleted'] == 'true') {
//     $message = '✅ Product deleted successfully!';
//     $messageType = 'success';
// }
?>
<!DOCTYPE html>
<html>
<head>
    <title>e-Mart | Admin Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            padding-top: 60px; /* Adjusted padding-top to match common navbar height */
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
            margin-left: 800px;
        }
        .navbar ul li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }
        .navbar ul li a:hover {
            color: #0074cc;
        }
         .navbar ul li a i {
            margin-right: 6px;
        }
        /* No active state needed for admin view unless you want to highlight current section */
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
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); /* FIXED: Responsive grid for admin view */
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
        .btn {
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
        <li><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="admin_upload.php"><i class="fas fa-store"></i> Products</a></li>
        <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
        <li><a href="index.php"><i class="fas fa-box"></i> Shop</a></li> <!-- Link to user shop for admin to view -->
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<h1>🛍️ All Products (Admin View)</h1>

<?php if (!empty($message)): ?>
    <div class="php-message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="filter-box">
    <form method="GET" action="">
        <label for="category">Filter by Category:</label>
        <select name="category" id="category" onchange="this.form.submit()">
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
                <?php if (!empty($product['ImagePath'])): // Use !empty() for better check ?>
                    <img src="<?php echo htmlspecialchars($product['ImagePath']); ?>" alt="product">
                <?php else: ?>
                    <img src="images/placeholder.png" alt="No image">
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($product['ProductName']); ?></h3>
                <p class="price">₹<?php echo htmlspecialchars($product['Price']); ?></p>
                <!-- Changed "Add to Cart" to "Edit Product" for admin view -->
                <a href="admin_edit_product.php?id=<?= htmlspecialchars($product['ID']) ?>" class="btn">Edit Product</a>
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
