<?php
session_start(); // Start the session at the very beginning

// --- AUTHENTICATION AND AUTHORIZATION CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Ensure user_cart_item_id is passed, as we're now working with persistent cart items
if (!isset($_GET['user_cart_item_id'])) {
    header("Location: user_cart.php?order_error=missing_cart_item_id");
    exit;
}

$userCartItemId = filter_var($_GET['user_cart_item_id'], FILTER_VALIDATE_INT);

if ($userCartItemId === false) {
    header("Location: user_cart.php?order_error=invalid_cart_item_id");
    exit;
}

// Database connection details
$serverName = "SUDIPTA\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "ClothingStoreDB",
    "TrustServerCertificate" => true
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    error_log("Database connection failed on select_size.php: " . print_r(sqlsrv_errors(), true));
    die("Database connection failed. Please try again later.");
}

// Fetch product details from UserCartItems (and ClothingItems via JOIN)
// Ensure the item belongs to the logged-in user
$productDetails = null;
$sql_fetch_item = "SELECT
                        uci.ID as UserCartItemID,
                        uci.ProductID,
                        uci.Quantity,
                        uci.Size as CartSize, -- Alias to differentiate from ClothingItems.Size if exists
                        ci.ProductName,
                        ci.Price,
                        ci.ImagePath
                    FROM
                        dbo.UserCartItems uci
                    JOIN
                        ClothingItems ci ON uci.ProductID = ci.ID
                    WHERE
                        uci.ID = ? AND uci.UserID = ?"; // Verify ownership
$params_fetch_item = array($userCartItemId, $_SESSION['user_id']);
$stmt_fetch_item = sqlsrv_query($conn, $sql_fetch_item, $params_fetch_item);

if ($stmt_fetch_item === false) {
    error_log("Database error fetching cart item details: " . print_r(sqlsrv_errors(), true));
    sqlsrv_close($conn);
    header("Location: user_cart.php?order_error=db_error_item_details");
    exit;
}

$productDetails = sqlsrv_fetch_array($stmt_fetch_item, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt_fetch_item);
sqlsrv_close($conn);

if (!$productDetails) {
    header("Location: user_cart.php?order_error=product_not_found_in_user_cart");
    exit;
}

// Pre-fill customer info from session or database (e.g., from dbo.Users table)
$customerName = '';
$customerPhone = $_SESSION['phone'] ?? ''; // Pre-fill with logged-in user's phone
$customerAddress = ''; // This would typically be fetched from a user profile table

// You can fetch FullName and Address from dbo.Users here if you want to pre-populate them
/*
$conn_user_data = sqlsrv_connect($serverName, $connectionOptions);
if ($conn_user_data) {
    $sql_user_data = "SELECT FullName, Address FROM dbo.Users WHERE ID = ?"; // Assuming Address column
    $stmt_user_data = sqlsrv_query($conn_user_data, $sql_user_data, array($_SESSION['user_id']));
    if ($stmt_user_data && $user_row = sqlsrv_fetch_array($stmt_user_data, SQLSRV_FETCH_ASSOC)) {
        $customerName = htmlspecialchars($user_row['FullName']);
        $customerAddress = htmlspecialchars($user_row['Address'] ?? ''); // Assume Address might be null
    }
    sqlsrv_free_stmt($stmt_user_data);
    sqlsrv_close($conn_user_data);
}
*/
?>
<!DOCTYPE html>
<html>
<head>
    <title>Select Size and Customer Info</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #f0f8ff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .order-details-box {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-sizing: border-box;
        }
        .order-details-box h2 {
            color: #333;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .order-details-box p {
            color: #555;
            font-size: 1.1em;
            margin-bottom: 20px;
        }
        .order-details-box .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        .order-details-box label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .order-details-box input[type="radio"] {
            margin-right: 8px;
        }
        .order-details-box label.radio-label {
            display: inline-block;
            margin-right: 15px;
            font-weight: normal;
        }
        .order-details-box input[type="text"],
        .order-details-box input[type="tel"],
        .order-details-box textarea {
            width: calc(100% - 20px);
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box;
        }
        .order-details-box textarea {
            resize: vertical;
            min-height: 80px;
        }
        .order-details-box button {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            transition: background-color 0.3s;
            margin-top: 20px;
        }
        .order-details-box button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="order-details-box">
        <h2>Complete Your Order</h2>
        <p>Product: <strong><?= htmlspecialchars($productDetails['ProductName']) ?></strong> (₹<?= htmlspecialchars($productDetails['Price']) ?>)</p>

        <form action="process_order.php" method="POST">
            <!-- Hidden inputs for product details and the specific cart item ID -->
            <input type="hidden" name="user_cart_item_id" value="<?= htmlspecialchars($productDetails['UserCartItemID']) ?>">
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($productDetails['ProductID']) ?>">
            <input type="hidden" name="product_price" value="<?= htmlspecialchars($productDetails['Price']) ?>">
            <input type="hidden" name="product_name" value="<?= htmlspecialchars($productDetails['ProductName']) ?>">
            <input type="hidden" name="product_quantity" value="<?= htmlspecialchars($productDetails['Quantity']) ?>">


            <div class="form-group">
                <label>Choose a size:</label><br>
                <label class="radio-label"><input type="radio" name="size" value="S" <?= ($productDetails['CartSize'] === 'S') ? 'checked' : '' ?> required> S</label>
                <label class="radio-label"><input type="radio" name="size" value="M" <?= ($productDetails['CartSize'] === 'M') ? 'checked' : '' ?>> M</label>
                <label class="radio-label"><input type="radio" name="size" value="L" <?= ($productDetails['CartSize'] === 'L') ? 'checked' : '' ?>> L</label>
                <label class="radio-label"><input type="radio" name="size" value="XL" <?= ($productDetails['CartSize'] === 'XL') ? 'checked' : '' ?>> XL</label>
                <label class="radio-label"><input type="radio" name="size" value="XXL" <?= ($productDetails['CartSize'] === 'XXL') ? 'checked' : '' ?>> XXL</label>
            </div>

            <div class="form-group">
                <label for="customerName">Your Full Name:</label>
                <input type="text" id="customerName" name="customer_name" value="<?= htmlspecialchars($customerName) ?>" required>
            </div>

            <div class="form-group">
                <label for="customerPhone">Phone Number:</label>
                <input type="tel" id="customerPhone" name="customer_phone" pattern="[0-9]{10}" placeholder="e.g., 1234567890" value="<?= htmlspecialchars($customerPhone) ?>" required>
                <small>Format: 10 digits only</small>
            </div>

            <div class="form-group">
                <label for="address">Delivery Address:</label>
                <textarea id="address" name="address" rows="3" required><?= htmlspecialchars($customerAddress) ?></textarea>
            </div>

            <button type="submit">Place Order</button>
        </form>
    </div>
</body>
</html>
