<?php
session_start(); // Start the session at the very beginning

// --- AUTHENTICATION AND AUTHORIZATION CHECK ---
// This block ensures only logged-in users with the 'user' role can access this page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php"); // Redirect to login page if not authenticated as a user
    exit(); // Terminate script execution
}

// Database connection details
$serverName = "SUDIPTA\\SQLEXPRESS"; // Your SQL Server instance name
$connectionOptions = array(
    "Database" => "ClothingStoreDB", // Your database name
    "TrustServerCertificate" => true
    // Add Uid and PWD if using SQL Server Authentication
    // "Uid" => "your_db_username",
    // "PWD" => "your_db_password"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    error_log("Database connection failed on user_order_details.php: " . print_r(sqlsrv_errors(), true));
    die("Database connection failed. Please try again later."); // Critical error, stop execution
}

$order = null;
$accessDenied = false; // Flag to indicate access denial
$message = ''; // Initialize message variable for error/info display
$messageType = ''; // Initialize messageType

$loggedInUserId = $_SESSION['user_id'];
$loggedInUserPhone = $_SESSION['phone']; // Use this to verify order ownership securely

if (isset($_GET['order_id'])) {
    $orderId = filter_var($_GET['order_id'], FILTER_VALIDATE_INT); // Sanitize the order_id

    if ($orderId === false) {
        $accessDenied = true; // Invalid ID format
        $message = "Invalid order ID format provided.";
        $messageType = 'error';
    } else {
        // Fetch order details for the given OrderID AND matching CustomerPhone
        // This is crucial for security: users can only view their own orders
        $sql = "SELECT
                    o.OrderID,
                    o.ProductID,
                    o.Quantity,
                    o.CustomerName,
                    o.CustomerPhone,
                    o.Address,
                    o.Size,
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
                    o.OrderID = ? AND o.CustomerPhone = ?"; // Check both OrderID and CustomerPhone for security
        $params = array($orderId, $loggedInUserPhone); // Use the phone from the session for verification
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            error_log("Database error fetching order details: " . print_r(sqlsrv_errors(), true));
            $accessDenied = true; // Treat as access denied for security/simplicity in display
            $message = "Error fetching order details due to a database issue.";
            $messageType = 'error';
        } else {
            $order = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);

            if (!$order) {
                // If no order is found or it doesn't belong to the user
                $accessDenied = true;
                $message = "Order not found or you do not have permission to view it.";
                $messageType = 'error';
            }
        }
    }
} else {
    // If order_id is not provided in the URL
    $accessDenied = true;
    $message = "No order ID provided in the URL.";
    $messageType = 'error';
}

sqlsrv_close($conn); // Close connection after fetching data

// Define the order status steps in their correct sequence
$statusSteps = ['Received', 'Packed', 'Shipped', 'Delivered'];
$currentStatusIndex = -1; // Default

if ($order && isset($order['Status'])) {
    $currentStatus = $order['Status'];
    $currentStatusIndex = array_search($currentStatus, $statusSteps);
    if ($currentStatusIndex === false) {
        // Fallback if status from DB isn't in our defined steps
        $currentStatusIndex = count($statusSteps) - 1; // Assume last step if unrecognized
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>e-Mart | Order Details</title>
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
            margin-left: auto; /* Pushes menu items to the right */
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
            max-width: 1000px; /* ORIGINAL VALUE from the provided code */
            width: 95%; /* FIXED: Added this to make it wider and responsive */
            margin: 30px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        /* Overriding the above .container for specific width increase for this card */
        /* This rule targets the specific type of card that holds order details */
        .order-details-card-container { /* Renamed from .container for clarity if this is a separate element */
            flex: 1; /* Pushes footer down */
            max-width: 500px; /* INCREASED MAX-WIDTH SIGNIFICANTLY */
            width: 65%; /* Ensure it takes up 95% of its parent's width, up to max-width */
            margin: 30px auto; /* Centers the card horizontally */
            background-color: #fff;
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
        .order-summary-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .product-image-detail {
            width: 120px;
            height: 120px;
            object-fit: contain;
            margin-right: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 5px;
        }
        .product-details-text {
            flex-grow: 1;
            text-align: left;
        }
        .product-details-text h2 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1.5em;
        }
        .product-details-text p {
            margin: 5px 0;
            color: #555;
            font-size: 1.1em;
        }
        .product-details-text strong {
            color: #333;
        }

        .order-info-section {
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .order-info-section h3 {
             margin-top: 0;
             margin-bottom: 15px;
             color: #333;
             font-size: 1.3em;
        }
        .order-info-section p {
            margin: 8px 0;
            font-size: 1.05em;
            color: #555;
        }
        .order-info-section strong {
            color: #333;
        }
        .order-status-timeline {
            display: flex;
            flex-direction: column;
            gap: 25px;
            padding-left: 20px;
            position: relative;
            margin-top: 30px;
        }
        .order-status-timeline::before {
            content: '';
            position: absolute;
            left: 5px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #ddd;
            z-index: 0;
        }
        .timeline-step {
            display: flex;
            align-items: center;
            position: relative;
        }
        .timeline-step .circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #ddd;
            border: 2px solid #ccc;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 0.8em;
            font-weight: bold;
            flex-shrink: 0;
            z-index: 1;
        }
        .timeline-step.active .circle {
            background-color: #28a745;
            border-color: #28a745;
        }
        .timeline-step.active-line::before {
            content: '';
            position: absolute;
            left: 5px;
            top: 0;
            bottom: -25px; /* Extends line to connect to next step */
            width: 2px;
            background-color: #28a745;
            z-index: 0;
        }
        .timeline-step:last-child.active-line::before {
            display: none; /* Hide line after last active step */
        }

        .timeline-step .content {
            margin-left: 20px;
            color: #555;
            font-size: 1.1em;
        }
        .timeline-step.active .content {
            font-weight: bold;
            color: #333;
        }
        .timeline-step .content span {
            display: block;
            font-size: 0.9em;
            color: #777;
            margin-top: 3px;
        }
        .error-message {
            text-align: center;
            color: #dc3545;
            font-size: 1.2em;
            margin-top: 50px;
        }
        /* PHP message styling, consistent with other pages */
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
            <li><a href="user_orders.php"><i class="fas fa-box"></i> Orders</a></li>
            <!-- Logout link, always available for logged-in users -->
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- The main container for order details, now with a new class name -->
    <div class="order-details-card-container">
        <?php if (!empty($message)): // Display general error/info messages if any ?>
            <div class="php-message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($order && !$accessDenied): ?>
            <h1>Order Details (ID: <?= htmlspecialchars($order['OrderID']) ?>)</h1>

            <div class="order-summary-header">
                <img src="<?= htmlspecialchars($order['ImagePath'] ?? 'images/placeholder.png') ?>" alt="<?= htmlspecialchars($order['ProductName']) ?>" class="product-image-detail">
                <div class="product-details-text">
                    <h2><?= htmlspecialchars($order['ProductName']) ?></h2>
                    <p><strong>Price:</strong> ₹<?= htmlspecialchars($order['Price']) ?></p>
                    <p><strong>Quantity:</strong> <?= htmlspecialchars($order['Quantity']) ?></p>
                    <p><strong>Total for Item:</strong> ₹<?= htmlspecialchars($order['Price'] * $order['Quantity']) ?></p>
                    <p><strong>Size:</strong> <?= htmlspecialchars($order['Size'] ?? 'N/A') ?></p>
                </div>
            </div>

            <div class="order-info-section">
                <h3>Delivery Information</h3>
                <p><strong>Customer Name:</strong> <?= htmlspecialchars($order['CustomerName']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($order['CustomerPhone']) ?></p>
                <p><strong>Address:</strong> <?= htmlspecialchars($order['Address']) ?></p>
                <p><strong>Order Placed On:</strong> <?= htmlspecialchars($order['OrderDate'] instanceof DateTime ? $order['OrderDate']->format('Y-m-d H:i:s') : 'N/A') ?></p>
            </div>

            <h2>Order Status</h2>
            <div class="order-status-timeline">
                <?php foreach ($statusSteps as $index => $step): ?>
                    <div class="timeline-step <?= ($index <= $currentStatusIndex) ? 'active' : '' ?>
                                             <?= ($index < $currentStatusIndex) ? 'active-line' : '' ?>">
                        <div class="circle">
                            <?php if ($index <= $currentStatusIndex): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <i class="far fa-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="content">
                            <?= htmlspecialchars($step) ?>
                            <?php if ($step === $order['Status']): ?>
                                <span>(Current Status)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: // Display generic error if order is null or access is denied ?>
            <h1 class="error-message">Order Details Not Available</h1>
            <p style="text-align: center; font-size: 1.2em; color: #888;"><?= htmlspecialchars($message) ?></p>
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
