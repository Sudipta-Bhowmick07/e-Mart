<?php
$serverName = "SUDIPTA\\SQLEXPRESS"; // DOUBLE backslashes \\ are required in PHP

$connectionOptions = array(
    "Database" => "ClothingStoreDB",
    "TrustServerCertificate" => true
    // "Authentication" => SQLSRV_AUTH_WINDOWS
);

// Try to connect
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn) {
    echo "<h2>✅ Connection successful!</h2>";
} else {
    echo "<h2>❌ Connection failed.</h2>";
    die(print_r(sqlsrv_errors(), true));
}
?>
