<?php
session_start(); // Start the session for managing user login state

// Database connection details (replace with your actual details)
$serverName = "SUDIPTA\\SQLEXPRESS"; // Your SQL Server instance name
$connectionOptions = [
    "Database" => "ClothingStoreDB",
    "TrustServerCertificate" => true
];

$conn = null; // Initialize connection to null
$message = ''; // Message to display to the user
$messageType = ''; // 'success' or 'error'

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($phone) || empty($password)) {
        $message = "Phone number and password are required.";
        $messageType = 'error';
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) { // Basic 10-digit phone number validation
        $message = "Please enter a valid 10-digit phone number.";
        $messageType = 'error';
    } else {
        // Establish database connection
        $conn = sqlsrv_connect($serverName, $connectionOptions);

        if ($conn === false) {
            // Handle connection error
            $message = "Database connection failed. Please try again later.";
            $messageType = 'error';
            error_log(print_r(sqlsrv_errors(), true)); // Log SQLSRV errors for debugging
        } else {
            // Prepare SQL statement to fetch user by Phone number from dbo.Users
            // Using actual column names: ID, Phone, PasswordHash, Role
            $sql = "SELECT ID, Phone, PasswordHash, Role FROM dbo.Users WHERE Phone = ?";
            $params = array($phone);

            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                // Handle query error
                $message = "Login failed due to a database error.";
                $messageType = 'error';
                error_log(print_r(sqlsrv_errors(), true)); // Log SQLSRV errors
            } else {
                $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

                if ($user) {
                    // Verify password using password_verify() as PasswordHash stores hashed passwords
                    if (password_verify($password, $user['PasswordHash'])) {
                        // Login successful
                        session_regenerate_id(true); // Regenerate session ID for security
                        $_SESSION['user_id'] = $user['ID'];
                        $_SESSION['phone'] = $user['Phone'];
                        $_SESSION['role'] = $user['Role']; // Store user role in session

                        // Redirect based on role
                        if ($user['Role'] === 'admin') {
                            header("Location: home.php");
                        } else {
                            header("Location: user_home.php");
                        }
                        exit(); // Important to exit after redirection
                    } else {
                        $message = "Invalid phone number or password.";
                        $messageType = 'error';
                    }
                } else {
                    $message = "Invalid phone number or password.";
                    $messageType = 'error';
                }
            }
            sqlsrv_free_stmt($stmt); // Free the statement resources
            sqlsrv_close($conn); // Close the database connection
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-Mart - User Login</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(to bottom right, #e0f7fa, #f1fdfd);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
        }
        .login-container {
            background-color: white;
            padding: 2.5rem;
            border-radius: 1rem; /* Rounded corners */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* Subtle shadow */
            max-width: 400px;
            width: 100%;
        }
        .input-field {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db; /* Light gray border */
            border-radius: 0.5rem; /* Rounded corners */
            font-size: 1rem;
            margin-bottom: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .input-field:focus {
            outline: none;
            border-color: #f87171; /* Red focus border */
            box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.3); /* Light red glow */
        }
        .login-button {
            width: 100%;
            background-color: #0074cc; /* Red color */
            color: white;
            padding: 0.75rem;
            border-radius: 0.5rem; /* Rounded corners */
            font-weight: 600;
            font-size: 1.125rem;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.1s ease;
        }
        .login-button:hover {
            background-color: #dc2626; /* Darker red on hover */
            transform: translateY(-1px);
        }
        .login-button:active {
            transform: translateY(0);
        }
        .register-link {
            color: #0074cc; /* Red link */
            font-weight: 500;
            transition: color 0.2s ease;
        }
        .register-link:hover {
            color: #dc2626; /* Darker red on hover */
            text-decoration: underline;
        }
        /* Message box styling for PHP feedback */
        .message-box {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 500;
            <?php echo empty($message) ? 'display: none;' : ''; ?> /* Display only if $message is not empty */
        }
        .message-box.error {
            background-color: #fef2f2; /* Light red */
            color: #dc2626; /* Darker red */
        }
        .message-box.success {
            background-color: #d1fae5; /* Light green */
            color: #065f46; /* Darker green */
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h2 class="text-3xl font-bold text-gray-800 text-center mb-8">User Login</h2>

        <!-- PHP Message Box for feedback -->
        <div id="messageBox" class="message-box <?php echo htmlspecialchars($messageType); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

        <form method="POST" action="login.php">
            <div class="mb-4">
                <label for="phone" class="block text-gray-700 text-sm font-medium mb-2">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="input-field" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" pattern="[0-9]{10}" title="Please enter a 10-digit phone number" required>
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                <input type="password" id="password" name="password" class="input-field" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="login-button">Login</button>
        </form>

        <p class="text-center text-gray-600 text-sm mt-6">
            Don't have an account? <a href="register.php" class="register-link">Register here</a>
        </p>
    </div>

</body>
</html>
