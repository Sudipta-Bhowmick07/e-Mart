<?php
session_start(); // Start the session at the very beginning

// --- AUTHENTICATION AND AUTHORIZATION CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Get the user's ID
$loggedInUserId = $_SESSION['user_id'];

// We now expect 'user_cart_item_id' instead of 'remove_index'
if (!isset($_GET['user_cart_item_id']) || !is_numeric($_GET['user_cart_item_id'])) {
    header("Location: user_cart.php?error=invalid_remove_request");
    exit;
}

$userCartItemId = filter_var($_GET['user_cart_item_id'], FILTER_VALIDATE_INT);

if ($userCartItemId === false) {
    header("Location: user_cart.php?error=invalid_remove_request_id_format");
    exit;
}

// Connect to SQL Server to fetch item details for display
$serverName = "SUDIPTA\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "ClothingStoreDB",
    "TrustServerCertificate" => true
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    error_log("Database connection failed on confirm_remove.php: " . print_r(sqlsrv_errors(), true));
    die("Database connection failed. Please try again later.");
}

// Fetch the item details to display for confirmation, ensuring it belongs to the logged-in user
$itemToRemove = null;
$sql_fetch_item = "SELECT
                        uci.ID as UserCartItemID,
                        uci.ProductID,
                        uci.Quantity,
                        uci.Size,
                        ci.ProductName,
                        ci.Price
                    FROM
                        dbo.UserCartItems uci
                    JOIN
                        ClothingItems ci ON uci.ProductID = ci.ID
                    WHERE
                        uci.ID = ? AND uci.UserID = ?"; // CRITICAL: Ensure ownership
$params_fetch_item = array($userCartItemId, $loggedInUserId);
$stmt_fetch_item = sqlsrv_query($conn, $sql_fetch_item, $params_fetch_item);

if ($stmt_fetch_item === false) {
    error_log("Database query error fetching item for removal confirmation: " . print_r(sqlsrv_errors(), true));
    sqlsrv_close($conn);
    header("Location: user_cart.php?removed=false&error=db_error_fetching_item");
    exit;
}

$itemToRemove = sqlsrv_fetch_array($stmt_fetch_item, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt_fetch_item);
sqlsrv_close($conn);

if (!$itemToRemove) {
    // Item not found in cart or does not belong to this user, redirect back
    header("Location: user_cart.php?removed=false&error=item_not_found_or_access_denied");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Confirm Removal</title>
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
        .confirmation-box {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-sizing: border-box;
        }
        .confirmation-box h2 {
            color: #333;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .confirmation-box p {
            color: #555;
            font-size: 1.1em;
            margin-bottom: 30px;
        }
        .confirmation-box .actions a {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin: 0 10px;
            transition: background-color 0.3s;
        }
        .confirmation-box .actions .yes-btn {
            background-color: #dc3545; /* Red for Yes */
            color: white;
        }
        .confirmation-box .actions .yes-btn:hover {
            background-color: #c82333;
        }
        .confirmation-box .actions .no-btn {
            background-color: #6c757d; /* Grey for No */
            color: white;
        }
        .confirmation-box .actions .no-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="confirmation-box">
        <h2>Confirm Removal</h2>
        <?php if ($itemToRemove): ?>
            <p>Are you sure you want to remove "<strong><?= htmlspecialchars($itemToRemove['ProductName']) ?></strong>" (Size: <?= htmlspecialchars($itemToRemove['Size'] ?? 'N/A') ?>) from your cart?</p>
        <?php else: ?>
            <p>Error: Item not found for removal.</p> <!-- Fallback message for unexpected cases -->
        <?php endif; ?>
        <div class="actions">
            <!-- Changed link to a new file that handles the actual database removal -->
            <a href="process_remove_cart_item.php?user_cart_item_id=<?= htmlspecialchars($userCartItemId) ?>" class="yes-btn">Yes, Remove</a>
            <a href="user_cart.php" class="no-btn">No, Cancel</a>
        </div>
    </div>
</body>
</html>
