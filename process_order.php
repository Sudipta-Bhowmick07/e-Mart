<?php
session_start(); // Start the session at the very beginning

// --- AUTHENTICATION AND AUTHORIZATION CHECK ---
// This block ensures only logged-in users with the 'user' role can access this page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Get logged-in user's ID and phone from session (for security and record-keeping)
$loggedInUserId = $_SESSION['user_id'];
$loggedInUserPhone = $_SESSION['phone']; // This is safe to use as it comes from a verified session

// Database connection details
$serverName = "SUDIPTA\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "ClothingStoreDB",
    "TrustServerCertificate" => true
    // Add Uid and PWD if using SQL Server Authentication (e.g., if connecting via SQL Server login)
    // "Uid" => "your_db_username",
    // "PWD" => "your_db_password"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    error_log("Database connection failed on process_order.php: " . print_r(sqlsrv_errors(), true));
    header("Location: user_cart.php?order_error=db_connection_failed");
    exit();
}

// 1. Receive POST Data from select_size.php
// Sanitize inputs
$userCartItemId = filter_var($_POST['user_cart_item_id'] ?? '', FILTER_VALIDATE_INT);
$productId = filter_var($_POST['product_id'] ?? '', FILTER_VALIDATE_INT);
// Note: product_price, product_name, product_quantity are passed as hidden fields.
// While we validate them here, for security in a production app, you would ideally
// re-fetch these from the ClothingItems table based on ProductID to prevent tampering.
$productPrice = filter_var($_POST['product_price'] ?? '', FILTER_VALIDATE_FLOAT);
$productName = $_POST['product_name'] ?? '';
$productQuantity = filter_var($_POST['product_quantity'] ?? '', FILTER_VALIDATE_INT);

$size = $_POST['size'] ?? '';
$customerName = $_POST['customer_name'] ?? '';
$customerPhone = $_POST['customer_phone'] ?? '';
$address = $_POST['address'] ?? '';

// 2. Validate received data
$errors = [];
if ($userCartItemId === false || $userCartItemId <= 0) {
    $errors[] = "Invalid cart item ID.";
}
if ($productId === false || $productId <= 0) {
    $errors[] = "Invalid product ID.";
}
if ($productPrice === false || $productPrice < 0) {
    $errors[] = "Invalid product price.";
}
if (empty($productName)) {
    $errors[] = "Product name is missing.";
}
if ($productQuantity === false || $productQuantity <= 0) {
    $errors[] = "Invalid product quantity.";
}
if (empty($size)) { // Size is required by your select_size.php form's 'required' attribute
    $errors[] = "Size is required.";
}
if (empty($customerName)) { // CustomerName is required by your select_size.php form
    $errors[] = "Customer name is required.";
}
if (!preg_match("/^[0-9]{10}$/", $customerPhone)) { // Phone is required and 10-digits
    $errors[] = "Please enter a valid 10-digit phone number.";
}
if (empty($address)) { // Address is required by your select_size.php form
    $errors[] = "Delivery address is required.";
}

if (!empty($errors)) {
    sqlsrv_close($conn); // Close DB connection before redirecting
    header("Location: user_cart.php?order_error=" . urlencode(implode(", ", $errors)));
    exit();
}

try {
    // Start a transaction for atomicity: either order is placed AND cart item is removed, or neither happens.
    if (sqlsrv_begin_transaction($conn) === false) {
        throw new Exception("Failed to begin transaction: " . print_r(sqlsrv_errors(), true));
    }

    // 3. Insert Order into the dbo.Orders table
    // Your table columns: ProductID, Quantity, Size, CustomerName, CustomerPhone, Address, Status, OrderDate
    // OrderID is IDENTITY, OrderDate is GETDATE()
    $orderStatus = 'Received'; // Initial status

    $sql_insert_order = "INSERT INTO dbo.Orders (ProductID, Quantity, Size, CustomerName, CustomerPhone, Address, Status, OrderDate) VALUES (?, ?, ?, ?, ?, ?, ?, GETDATE())";
    $params_insert_order = array(
        $productId,
        $productQuantity,
        $size,
        $customerName,
        $customerPhone,
        $address,
        $orderStatus
        // GETDATE() is handled by SQL Server, no placeholder needed
    );

    $stmt_insert_order = sqlsrv_query($conn, $sql_insert_order, $params_insert_order);

    if ($stmt_insert_order === false) {
        throw new Exception("Error inserting order into database: " . print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt_insert_order); // Free the statement resource

    // 4. Delete the corresponding item from dbo.UserCartItems table
    // Ensure that only the logged-in user can delete their own cart item for security
    $sql_delete_cart_item = "DELETE FROM dbo.UserCartItems WHERE ID = ? AND UserID = ?";
    $params_delete_cart_item = array($userCartItemId, $loggedInUserId);
    $stmt_delete_cart_item = sqlsrv_query($conn, $sql_delete_cart_item, $params_delete_cart_item);

    if ($stmt_delete_cart_item === false) {
        throw new Exception("Error deleting cart item from database: " . print_r(sqlsrv_errors(), true));
    }
    // Check if any rows were affected (meaning an item was actually deleted)
    $rowsAffected = sqlsrv_rows_affected($stmt_delete_cart_item);
    if ($rowsAffected === false || $rowsAffected === 0) {
         // This might happen if the item was already deleted or did not belong to the user
         // We can still commit the order as it was inserted successfully, but log this warning.
         error_log("Warning: Cart item ID {$userCartItemId} not found or not owned by user {$loggedInUserId} during deletion after order placement.");
    }
    sqlsrv_free_stmt($stmt_delete_cart_item); // Free the statement resource

    // If all operations successful, commit the transaction
    sqlsrv_commit($conn);
    sqlsrv_close($conn); // Close DB connection

    // 5. Redirect on success
    header("Location: user_cart.php?order_placed=true");
    exit();

} catch (Exception $e) {
    // Rollback transaction on any error
    sqlsrv_rollback($conn);
    sqlsrv_close($conn); // Close DB connection

    error_log("Order processing error: " . $e->getMessage());
    header("Location: user_cart.php?order_error=" . urlencode($e->getMessage()));
    exit();
}
?>
