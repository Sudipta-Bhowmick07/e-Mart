<?php
$serverName = "SUDIPTA\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "ClothingStoreDB",
    "TrustServerCertificate" => true
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

session_start();

// Basic Admin Authentication (IMPORTANT: Implement proper authentication for production)
// For now, let's assume if this page is accessed, it's an admin.
// In a real application, you'd check $_SESSION['admin_logged_in'] or similar.
// For testing, we'll allow access for now, but seriously consider adding authentication.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // You might want to redirect to an admin login page here, for example:
    // header("Location: admin_login.php");
    // exit;
    error_log("Access to admin_orders.php without proper admin login.");
}


// Fetch all orders
$sql = "SELECT OrderID, ProductID, Quantity, CustomerName, CustomerPhone, Address, Size, Status, OrderDate FROM Orders ORDER BY OrderDate DESC";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$orders = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $orders[] = $row;
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

// Possible order status messages
$statusMessages = [
    'updated' => '✅ Order status updated successfully!',
    'error' => '❌ Error updating order status.',
    'invalid_request' => '❌ Invalid request for status update.',
    'db_connection_error' => '❌ Database connection error for status update.'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>e-Mart Admin | Orders</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding: 0;
            padding-top: 80px; /* Adjust padding-top to match your navbar height */
            background: linear-gradient(to bottom right, #e0f7fa, #f1fdfd);
            min-height: 100vh;
        }
        .navbar {
    position: fixed;
    top: 0;
    width: 100%;
    background: #fff;
    border-bottom: 2px solid #f2f2f2;
    padding: 15px 50px; /* 👈 increased left-right padding */
    display: flex;
    align-items: center;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    gap: 50px; /* 👈 added space between logo and menu */
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
    margin-left: 800px; /* ✅ just slightly shifts the menu left */
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
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
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
        .orders-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 10px;
        }
        .order-card {
            background: #fffde7;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }
        .order-info {
            flex: 2;
            min-width: 300px;
            margin-right: 20px;
        }
        .order-info p {
            margin: 5px 0;
            color: #555;
        }
        .order-info strong {
            color: #333;
        }
        .order-status-update {
            flex: 1;
            min-width: 250px;
            text-align: right;
        }
        .order-status-update label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .order-status-update select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .order-status-update button {
            background-color: #007bff;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .order-status-update button:hover {
            background-color: #0056b3;
        }
        .no-orders {
            text-align: center;
            color: #888;
            margin-top: 50px;
            font-size: 1.1em;
        }
        .product-details {
            font-weight: bold;
            color: #0074cc;
        }
    </style>
</head>
<body>

<div class="navbar">
    <div class="logo">e-Mart</div>
    <ul>
        <li><a href="home.php"><i class="fas fa-home"></i>  Home</a></li>
        <li><a href="admin_upload.php"><i class="fas fa-store"></i> Products</a></li> <!-- Changed icon to fa-box -->
        <li class="active"><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li> <!-- Changed icon to fa-receipt -->
        <li><a href="index.php"><i class="fas fa-box"></i> Shop</a></li> <!-- Corrected link and icon for Shop -->
        <!-- Logout option is removed as requested -->
                 <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>

    </ul>
</div>

<h1>📦 Manage Customer Orders</h1>

<?php if (isset($_GET['status_update'])): ?>
    <?php
        $msgType = $_GET['status_update'];
        if (isset($statusMessages[$msgType])) {
            $class = ($msgType == 'updated') ? 'success' : 'error';
            echo "<p class=\"message {$class}\">" . htmlspecialchars($statusMessages[$msgType]) . "</p>";
        }
    ?>
<?php endif; ?>

<div class="orders-container">
    <?php if (empty($orders)): ?>
        <p class="no-orders">No orders have been placed yet.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-info">
                    <p><strong>Order ID:</strong> <?= htmlspecialchars($order['OrderID']) ?></p>
                    <p class="product-details">Product ID: <?= htmlspecialchars($order['ProductID']) ?> (Size: <?= htmlspecialchars($order['Size'] ?? 'N/A') ?>)</p>
                    <p><strong>Quantity:</strong> <?= htmlspecialchars($order['Quantity']) ?></p>
                    <p><strong>Customer:</strong> <?= htmlspecialchars($order['CustomerName']) ?> (<?= htmlspecialchars($order['CustomerPhone']) ?>)</p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($order['Address']) ?></p>
                    <p><strong>Order Date:</strong> <?= htmlspecialchars($order['OrderDate']->format('Y-m-d H:i:s')) ?></p>
                    <p><strong>Current Status:</strong> <span style="font-weight: bold; color: #0056b3;"><?= htmlspecialchars($order['Status']) ?></span></p>
                </div>
                <div class="order-status-update">
                    <form action="update_order_status.php" method="POST">
                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['OrderID']) ?>">
                        <label for="status_<?= htmlspecialchars($order['OrderID']) ?>">Update Status:</label>
                        <select name="new_status" id="status_<?= htmlspecialchars($order['OrderID']) ?>">
                            <option value="Received" <?= ($order['Status'] == 'Received') ? 'selected' : '' ?>>Received</option>
                            <option value="Packed" <?= ($order['Status'] == 'Packed') ? 'selected' : '' ?>>Packed</option>
                            <option value="Shipped" <?= ($order['Status'] == 'Shipped') ? 'selected' : '' ?>>Shipped</option>
                            <option value="Delivered" <?= ($order['Status'] == 'Delivered') ? 'selected' : '' ?>>Delivered</option>
                        </select>
                        <button type="submit">Update</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>