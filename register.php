<?php
session_start(); // Start the session, mostly for potential success/error messages

// Database connection details (replace with your actual details)
$serverName = "SUDIPTA\\SQLEXPRESS"; // Your SQL Server instance name
$connectionOptions = [
    "Database" => "ClothingStoreDB",
    "TrustServerCertificate" => true
];

$conn = null; // Initialize connection to null
$message = ''; // Message to display to the user
$messageType = ''; // 'success' or 'error'

// Process registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullName = $_POST['name'] ?? '';       // Mapped to FullName
    $phone = $_POST['phone'] ?? '';        // Mapped to Phone
    $email = $_POST['email'] ?? NULL;      // Mapped to Email (can be NULL)
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Server-side validation
    if (empty($fullName) || empty($phone) || empty($password) || empty($confirmPassword)) {
        $message = "Full Name, Phone, and Password fields are required."; // Adjusted message
        $messageType = 'error';
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) { // Robust 10-digit phone number validation
        $message = "Please enter a valid 10-digit phone number.";
        $messageType = 'error';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) { // Validate email if provided
        $message = "Please enter a valid email address.";
        $messageType = 'error';
    }
    elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $messageType = 'error';
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = 'error';
    } else {
        // Establish database connection
        $conn = sqlsrv_connect($serverName, $connectionOptions);

        if ($conn === false) {
            $message = "Database connection failed. Please try again later.";
            $messageType = 'error';
            error_log(print_r(sqlsrv_errors(), true)); // Log SQLSRV errors for debugging
        } else {
            // Check if phone number already exists to prevent duplicate registrations
            $sql_check = "SELECT ID FROM dbo.Users WHERE Phone = ?"; // Using actual column name 'Phone'
            $params_check = array($phone);
            $stmt_check = sqlsrv_query($conn, $sql_check, $params_check);

            if ($stmt_check === false) {
                $message = "Registration failed due to a database error during phone check.";
                $messageType = 'error';
                error_log(print_r(sqlsrv_errors(), true));
            } else {
                if (sqlsrv_has_rows($stmt_check)) {
                    $message = "Phone number is already registered. Please login or use a different number.";
                    $messageType = 'error';
                } else {
                    // Hash the password for security before storing
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    // AUTOMATICALLY SET ROLE TO 'user' for all new registrations
                    $role = 'user'; 

                    // Insert new user into the dbo.Users table
                    // Using actual column names: FullName, Phone, PasswordHash, Email, RegistrationDate, Role
                    // Note: Email is passed as a parameter now, can be NULL if not provided in form
                    $sql_insert = "INSERT INTO dbo.Users (FullName, Phone, PasswordHash, Email, RegistrationDate, Role) VALUES (?, ?, ?, ?, GETDATE(), ?)";
                    $params_insert = array($fullName, $phone, $hashed_password, $email, $role); // $email is now included

                    $stmt_insert = sqlsrv_query($conn, $sql_insert, $params_insert);

                    if ($stmt_insert === false) {
                        $message = "Registration failed. Please try again.";
                        $messageType = 'error';
                        error_log(print_r(sqlsrv_errors(), true));
                    } else {
                        $message = "Registration successful! You can now login with your phone number and password.";
                        $messageType = 'success';
                        // Clear form fields after successful registration for a clean slate
                        $_POST = array(); // Clears all POST data, so input fields empty on refresh
                        // You could also redirect to the login page immediately:
                        // header("Location: login.php?registration_success=1");
                        // exit();
                    }
                    sqlsrv_free_stmt($stmt_insert);
                }
                sqlsrv_free_stmt($stmt_check);
            }
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
    <title>e-Mart - User Registration</title>
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
        .register-container {
            background-color: white;
            padding: 2.5rem;
            border-radius: 1rem; /* Rounded corners */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* Subtle shadow */
            max-width: 450px;
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
        .register-button {
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
        .register-button:hover {
            background-color: #dc2626; /* Darker red on hover */
            transform: translateY(-1px);
        }
        .register-button:active {
            transform: translateY(0);
        }
        .login-link {
            color: #0074cc; /* Red link */
            font-weight: 500;
            transition: color 0.2s ease;
        }
        .login-link:hover {
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

    <div class="register-container">
        <h2 class="text-3xl font-bold text-gray-800 text-center mb-8">User Registration</h2>

        <!-- PHP Message Box for feedback -->
        <div id="messageBox" class="message-box <?php echo htmlspecialchars($messageType); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

        <form method="POST" action="register.php">
            <div class="mb-4">
                <label for="name" class="block text-gray-700 text-sm font-medium mb-2">Full Name</label>
                <input type="text" id="name" name="name" class="input-field" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>
            <div class="mb-4">
                <label for="phone" class="block text-gray-700 text-sm font-medium mb-2">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="input-field" placeholder="Enter your 10-digit phone number" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" pattern="[0-9]{10}" title="Please enter a 10-digit phone number" required>
            </div>
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-medium mb-2">Email (Optional)</label>
                <input type="email" id="email" name="email" class="input-field" placeholder="Enter your email address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-medium mb-2">Password</label>
                <input type="password" id="password" name="password" class="input-field" placeholder="Create a password" required>
            </div>
            <div class="mb-6">
                <label for="confirm_password" class="block text-gray-700 text-sm font-medium mb-2">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="input-field" placeholder="Confirm your password" required>
            </div>
            <button type="submit" class="register-button">Register</button>
        </form>

        <p class="text-center text-gray-600 text-sm mt-6">
            Already have an account? <a href="login.php" class="login-link">Login here</a>
        </p>
    </div>

</body>
</html>
