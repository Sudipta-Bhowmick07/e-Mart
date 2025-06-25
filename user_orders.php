<?php
session_start(); // Start the session at the very beginning

// --- AUTHENTICATION AND AUTHORIZATION CHECK ---
// This block ensures only logged-in users with the 'user' role can access this page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php"); // Redirect to login page if not authenticated as a user
    exit(); // Terminate script execution
}

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
    error_log("Database connection failed on user_orders.php: " . print_r(sqlsrv_errors(), true));
    die("Database connection failed. Please try again later."); // Critical error, stop execution
}

// Use $_SESSION['user_id'] to get user's phone for query, as user_id is the primary identifier
$loggedInUserId = $_SESSION['user_id'];
$loggedInUserPhone = $_SESSION['phone']; // This is safe to use as it comes from a verified session

$userOrders = [];

// Fetch all orders for the logged-in user's phone number
// JOIN with ClothingItems to get ProductName, Price, and ImagePath
// Also ensure the order belongs to the current user's phone for security (redundant but good practice with session check)
$sql = "SELECT
            o.OrderID,
            o.ProductID,
            o.Quantity,
            o.Status,
            o.OrderDate,
            ci.ProductName,
            ci.Price,
            ci.ImagePath
        FROM
            Orders o
        JOIN
            ClothingItems ci ON o.ProductID = ci.ID
        WHERE
            o.CustomerPhone = ?
        ORDER BY
            o.OrderDate DESC";
$params = array($loggedInUserPhone); // Use phone for CustomerPhone column
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    error_log("Database error fetching user orders: " . print_r(sqlsrv_errors(), true));
    // Set an empty array or handle error gracefully in HTML
} else {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Handle DateTime objects from SQLSRV if necessary, for display
        if ($row['OrderDate'] instanceof DateTime) {
            // No need to format here, do it in HTML for flexibility
        }
        $userOrders[] = $row;
    }
    sqlsrv_free_stmt($stmt);
}

sqlsrv_close($conn); // Close connection after fetching data
?>

<!DOCTYPE html>
<html>
<head>
    <title>e-Mart | My Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding-top: 60px; /* Space for fixed navbar */
            background: linear-gradient(to bottom right, #e0f7fa, #f1fdfd);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Navbar styles from the provided HTML file */
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
        }


        .container {
            flex: 1; /* Pushes footer down */
            max-width: 900px;
            margin: 30px auto;
            background: #fffde7;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .order-list {
            display: grid;
            gap: 20px;
        }
        .order-item {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .order-item-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-right: 20px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .order-item-info {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .order-item-info p {
            margin: 5px 0;
            color: #555;
        }
        .order-item-info strong {
            color: #333;
        }
        .order-item-actions {
            margin-left: auto;
        }
        .order-item .view-details-btn {
            background-color: #007bff;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
            white-space: nowrap;
        }
        .order-item .view-details-btn:hover {
            background-color: #0056b3;
        }
        .no-orders-message {
            text-align: center;
            color: #888;
            font-size: 1.1em;
            margin-top: 40px;
        }

        /* Footer styles from the provided HTML file */
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
            <li><a href="user_cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
            <li class="active"><a href="user_orders.php"><i class="fas fa-box"></i> Orders</a></li>
            <!-- Logout link, always available for logged-in users -->
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <div class="container">
        <h1>My Orders</h1>

        <?php if (empty($userOrders)): ?>
            <p class="no-orders-message">You haven't placed any orders yet.</p>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($userOrders as $order): ?>
                    <div class="order-item">
                        <img src="<?= htmlspecialchars($order['ImagePath'] ?? 'images/placeholder.png') ?>" alt="<?= htmlspecialchars($order['ProductName']) ?>" class="order-item-img">
                        <div class="order-item-info">
                            <p><strong>Order ID:</strong> <?= htmlspecialchars($order['OrderID']) ?></p>
                            <p><strong>Product:</strong> <?= htmlspecialchars($order['ProductName']) ?></p>
                            <p><strong>Price:</strong> ₹<?= htmlspecialchars($order['Price']) ?> x <?= htmlspecialchars($order['Quantity']) ?> = ₹<?= htmlspecialchars($order['Price'] * $order['Quantity']) ?></p>
                            <p><strong>Current Status:</strong> <span style="font-weight: bold; color: #007bff;"><?= htmlspecialchars($order['Status']) ?></span></p>
                            <p><strong>Order Date:</strong> <?= htmlspecialchars($order['OrderDate'] instanceof DateTime ? $order['OrderDate']->format('Y-m-d H:i:s') : 'N/A') ?></p>
                        </div>
                        <div class="order-item-actions">
                            <a href="user_order_details.php?order_id=<?= htmlspecialchars($order['OrderID']) ?>" class="view-details-btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
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
