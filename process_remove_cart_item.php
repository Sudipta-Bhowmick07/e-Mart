<?php
session_start(); // Start the session

// --- AUTHENTICATION AND AUTHORIZATION CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

// Get the user's ID
$loggedInUserId = $_SESSION['user_id'];

// Get the UserCartItemID from the URL
if (!isset($_GET['user_cart_item_id']) || !is_numeric($_GET['user_cart_item_id'])) {
    header("Location: user_cart.php?error=invalid_remove_request");
    exit;
}

$userCartItemId = filter_var($_GET['user_cart_item_id'], FILTER_VALIDATE_INT);

if ($userCartItemId === false) {
    header("Location: user_cart.php?error=invalid_remove_request_id_format");
    exit;
}

// Connect to SQL Server
$serverName = "SUDIPTA\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "ClothingStoreDB",
    "TrustServerCertificate" => true
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    error_log("Database connection failed on process_remove_cart_item.php: " . print_r(sqlsrv_errors(), true));
    header("Location: user_cart.php?removed=false&error=db_connection_failed");
    exit;
}

// Delete the cart item from the database, ensuring it belongs to the logged-in user
$sql_delete_item = "DELETE FROM dbo.UserCartItems WHERE ID = ? AND UserID = ?";
$params_delete_item = array($userCartItemId, $loggedInUserId);
$stmt_delete_item = sqlsrv_query($conn, $sql_delete_item, $params_delete_item);

if ($stmt_delete_item === false) {
    error_log("Database error deleting cart item: " . print_r(sqlsrv_errors(), true));
    sqlsrv_close($conn);
    header("Location: user_cart.php?removed=false&error=db_delete_failed");
    exit;
}

// Check if any rows were affected (meaning an item was actually deleted)
$rowsAffected = sqlsrv_rows_affected($stmt_delete_item);
sqlsrv_free_stmt($stmt_delete_item);
sqlsrv_close($conn);

if ($rowsAffected > 0) {
    // If deleted successfully, redirect to cart with success message
    header("Location: user_cart.php?removed=true");
} else {
    // Item might not have been found (e.g., already deleted, or not owned by user)
    header("Location: user_cart.php?removed=false&error=item_not_found_or_not_owned");
}
exit;
?>
