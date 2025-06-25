<?php
$serverName = "SUDIPTA\\SQLEXPRESS";
$connectionOptions = array(
    "Database" => "ClothingStoreDB",
    "TrustServerCertificate" => true
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    error_log("Database connection error in update_order_status.php: " . print_r(sqlsrv_errors(), true));
    header("Location: admin_orders.php?status_update=db_connection_error");
    exit;
}

session_start();

// Basic Admin Authentication (IMPORTANT: Re-check authentication here too)
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header("Location: admin_login.php");
//     exit;
// }

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_id'], $_POST['new_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['new_status'];

    // Validate the new status to prevent invalid data insertion
    $allowedStatuses = ['Received', 'Packed', 'Shipped', 'Delivered'];
    if (!in_array($newStatus, $allowedStatuses)) {
        sqlsrv_close($conn);
        header("Location: admin_orders.php?status_update=invalid_request");
        exit;
    }

    // --- Start of new logic for inventory decrement ---
    // If the new status is 'Delivered', we need to decrement product quantity
    if ($newStatus == 'Delivered') {
        // 1. Get current order details (ProductID and Quantity) from the Orders table
        $getOrderDetailsSql = "SELECT ProductID, Quantity FROM Orders WHERE OrderID = ?";
        $getOrderDetailsStmt = sqlsrv_query($conn, $getOrderDetailsSql, array($orderId));

        if ($getOrderDetailsStmt === false) {
            error_log("Failed to fetch order details for inventory update: " . print_r(sqlsrv_errors(), true));
            sqlsrv_close($conn);
            header("Location: admin_orders.php?status_update=error"); // General error
            exit;
        }

        $orderDetails = sqlsrv_fetch_array($getOrderDetailsStmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($getOrderDetailsStmt);

        if ($orderDetails) {
            $productId = $orderDetails['ProductID'];
            $orderedQuantity = $orderDetails['Quantity'];

            // 2. Decrement quantity in ClothingItems table
            $updateClothingSql = "UPDATE ClothingItems SET Quantity = Quantity - ? WHERE ID = ?";
            $updateClothingParams = array($orderedQuantity, $productId);
            $updateClothingStmt = sqlsrv_query($conn, $updateClothingSql, $updateClothingParams);

            if ($updateClothingStmt === false) {
                error_log("Failed to update ClothingItems quantity for ProductID {$productId}: " . print_r(sqlsrv_errors(), true));
                // Even if inventory update fails, we might still want to update order status,
                // but for now, we'll treat it as a critical error.
                sqlsrv_close($conn);
                header("Location: admin_orders.php?status_update=error");
                exit;
            }
            sqlsrv_free_stmt($updateClothingStmt);
        } else {
            // Order not found, which should not happen if orderId is valid from admin_orders.php
            error_log("Order with ID {$orderId} not found when trying to decrement inventory.");
            sqlsrv_close($conn);
            header("Location: admin_orders.php?status_update=error");
            exit;
        }
    }
    // --- End of new logic for inventory decrement ---


    // 3. Update the order status in the Orders table (original logic)
    $sql = "UPDATE Orders SET Status = ? WHERE OrderID = ?";
    $params = array($newStatus, $orderId);

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        error_log("Failed to update order status for OrderID {$orderId}: " . print_r(sqlsrv_errors(), true));
        sqlsrv_close($conn);
        header("Location: admin_orders.php?status_update=error");
        exit;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    header("Location: admin_orders.php?status_update=updated");
    exit;

} else {
    sqlsrv_close($conn);
    header("Location: admin_orders.php?status_update=invalid_request");
    exit;
}
?>