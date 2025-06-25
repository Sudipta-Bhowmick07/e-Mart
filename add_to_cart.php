<?php
session_start(); // Start the session at the very beginning

// --- AUTHENTICATION AND AUTHORIZATION CHECK ---
// This block ensures only logged-in users with the 'user' role can add items to cart.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php?error=auth_required");
    exit(); // Terminate script execution
}

// Get logged-in user's ID
$loggedInUserId = $_SESSION['user_id'];

if (isset($_GET['id'])) {
    $productId = filter_var($_GET['id'], FILTER_VALIDATE_INT); // Sanitize product ID

    if ($productId === false) {
        header("Location: user_shop.php?error=invalid_id");
        exit;
    }

    // Connect to SQL Server
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
        error_log("Database connection error in add_to_cart.php: " . print_r(sqlsrv_errors(), true));
        header("Location: user_shop.php?error=db_connection");
        exit;
    }

    // First, get the product details from ClothingItems to ensure it's valid and get current price/image
    $sql_product_info = "SELECT ID, ProductName, Price, ImagePath FROM ClothingItems WHERE ID = ?";
    $stmt_product_info = sqlsrv_query($conn, $sql_product_info, array($productId));

    if ($stmt_product_info === false) {
        error_log("Database query error fetching product info in add_to_cart.php: " . print_r(sqlsrv_errors(), true));
        sqlsrv_close($conn);
        header("Location: user_shop.php?error=db_query");
        exit;
    }
    $product = sqlsrv_fetch_array($stmt_product_info, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt_product_info);

    if (!$product) {
        sqlsrv_close($conn);
        header("Location: user_shop.php?error=notfound");
        exit;
    }

    // --- PERSISTENT CART LOGIC (Database) ---
    // For now, we'll assume a default size 'M' if not chosen explicitly.
    // In a real scenario, you'd get the selected size from a form if "Add to Cart" implies size selection.
    // For "Add to Cart" directly from shop, usually adds default size or requires pop-up.
    // Since your cart currently stores individual items, we'll treat each "Add to Cart" as quantity 1 for now.
    // If you plan to have a size selector on user_shop.php, you'd need to pass it via GET here.
    $size = 'Default'; // Or null, or a default size. Adjust as per your ClothingItems/Orders table.

    // Check if the product already exists in the user's cart in the database
    $sql_check_cart = "SELECT ID, Quantity FROM dbo.UserCartItems WHERE UserID = ? AND ProductID = ? AND Size = ?";
    $params_check_cart = array($loggedInUserId, $productId, $size);
    $stmt_check_cart = sqlsrv_query($conn, $sql_check_cart, $params_check_cart);

    if ($stmt_check_cart === false) {
        error_log("Database error checking existing cart item: " . print_r(sqlsrv_errors(), true));
        sqlsrv_close($conn);
        header("Location: user_shop.php?error=db_query_cart_check");
        exit;
    }

    $existingCartItem = sqlsrv_fetch_array($stmt_check_cart, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt_check_cart);

    if ($existingCartItem) {
        // Item exists, update quantity
        $newQuantity = $existingCartItem['Quantity'] + 1;
        $sql_update_cart = "UPDATE dbo.UserCartItems SET Quantity = ? WHERE ID = ?";
        $params_update_cart = array($newQuantity, $existingCartItem['ID']);
        $stmt_update_cart = sqlsrv_query($conn, $sql_update_cart, $params_update_cart);

        if ($stmt_update_cart === false) {
            error_log("Database error updating cart item quantity: " . print_r(sqlsrv_errors(), true));
            sqlsrv_close($conn);
            header("Location: user_shop.php?error=db_update_cart");
            exit;
        }
        sqlsrv_free_stmt($stmt_update_cart);
    } else {
        // Item does not exist, insert new item into cart
        $sql_insert_cart = "INSERT INTO dbo.UserCartItems (UserID, ProductID, Quantity, Size) VALUES (?, ?, ?, ?)";
        $params_insert_cart = array($loggedInUserId, $productId, 1, $size); // Default quantity 1
        $stmt_insert_cart = sqlsrv_query($conn, $sql_insert_cart, $params_insert_cart);

        if ($stmt_insert_cart === false) {
            error_log("Database error inserting new cart item: " . print_r(sqlsrv_errors(), true));
            sqlsrv_close($conn);
            header("Location: user_shop.php?error=db_insert_cart");
            exit;
        }
        sqlsrv_free_stmt($stmt_insert_cart);
    }

    sqlsrv_close($conn);

    // --- SESSION CART LOGIC (Temporary/Synchronization) ---
    // This part is for immediate display. When user_cart.php loads, it will fetch from DB.
    // If you still want to maintain a session cart for other uses, you can keep this.
    // Otherwise, you can remove this section if the database is the ONLY source of truth.
    // For now, let's keep it in sync for immediate feedback.
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    // Check if the product already exists in the session cart to update its quantity
    $foundInSession = false;
    foreach ($_SESSION['cart'] as $index => &$cartSessionItem) { // Use & for reference
        if ($cartSessionItem['ID'] == $productId && ($cartSessionItem['Size'] ?? 'Default') == $size) {
            $cartSessionItem['Quantity']++;
            $foundInSession = true;
            break;
        }
    }
    if (!$foundInSession) {
        $_SESSION['cart'][] = [
            'ID' => $product['ID'],
            'Name' => $product['ProductName'],
            'Price' => $product['Price'],
            'Quantity' => 1,
            'Image' => $product['ImagePath'] ?? 'images/placeholder.png',
            'Size' => $size // Store size in session cart too
        ];
    }


    // Redirect back to user_shop.php with a success message flag
    header("Location: user_shop.php?added_to_cart=true");
    exit;

} else {
    // If 'id' is not provided in the URL
    header("Location: user_shop.php?error=invalid_id");
    exit;
}
?>
