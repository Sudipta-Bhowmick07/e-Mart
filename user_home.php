<?php
session_start(); // Start the session at the very beginning

// --- AUTHENTICATION AND AUTHORIZATION CHECK ---
// This block ensures only logged-in users with the 'user' role can access this page.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php"); // Redirect to login page if not authenticated as a user
    exit(); // Terminate script execution to prevent further processing
}

// Optionally, you can fetch user-specific data here using $_SESSION['user_id']
// Example: Fetch user's full name to display "Welcome, [User Name]!"
/*
$serverName = "SUDIPTA\\SQLEXPRESS";
$connectionOptions = [
    "Database" => "ClothingStoreDB",
    "TrustServerCertificate" => true
    // Add Uid and PWD if using SQL Server Authentication
    // "Uid" => "your_db_username",
    // "PWD" => "your_db_password"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

$loggedInUserName = 'Guest'; // Default name

if ($conn) {
    $sql = "SELECT FullName FROM dbo.Users WHERE ID = ?"; // Using your actual column name 'FullName'
    $params = array($_SESSION['user_id']);
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        error_log("Error fetching user name: " . print_r(sqlsrv_errors(), true));
    } else {
        if ($user_data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $loggedInUserName = htmlspecialchars($user_data['FullName']);
        }
        sqlsrv_free_stmt($stmt);
    }
    sqlsrv_close($conn);
} else {
    error_log("Database connection error on user_home.php: " . print_r(sqlsrv_errors(), true));
}
*/
?>
<!DOCTYPE html>
<html>
<head>
    <title>e-Mart Home</title>
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding-top: 60px; /* Space for the fixed navbar */
            background: linear-gradient(to bottom right, #e0f7fa, #f1fdfd);
            min-height: 100vh; /* Ensure body takes at least full viewport height */
            display: flex;
            flex-direction: column; /* For footer to stick to bottom */
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: #fff;
            border-bottom: 2px solid #f2f2f2;
            padding: 15px 50px;
            display: flex;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            gap: 50px;
            box-sizing: border-box; /* Include padding in element's total width and height */
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
            margin-left: auto; /* Pushes the menu items to the right */
        }
        .navbar ul li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            display: flex;
            align-items: center;
            padding: 5px 10px; /* Added padding for better click area */
            border-radius: 5px; /* Subtle rounding */
            transition: color 0.3s, background-color 0.3s;
        }
        .navbar ul li a:hover {
            color: #0074cc;
            background-color: #e6f7ff; /* Light background on hover */
        }
        .navbar ul li a i {
            margin-right: 6px;
        }
        .navbar ul li.active a {
            color: #0074cc;
            font-weight: bold;
            /* background-color: #e6f7ff; Consider if active state also has background */
        }

        /* Carousel Styles */
        .carousel {
            position: relative;
            width: 95%; /* FIXED: Increased percentage for wider content */
            max-width: 1400px; /* Still retain a max-width for very large screens */
            margin: 40px auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            flex-grow: 1; /* Allow carousel area to grow */
        }

        .carousel .slides {
            position: relative;
        }

        .carousel .slide {
            display: none;
            position: relative;
        }

        .carousel .slide img {
            width: 100%;
            display: block;
            height: 500px; /* INCREASED HEIGHT for better visual impact */
            object-fit: cover;
        }

        .carousel .caption {
            position: absolute;
            bottom: 20px;
            left: 30px;
            color: white;
            text-shadow: 0 2px 5px rgba(0,0,0,0.7);
        }

        .carousel .caption h3 {
            margin: 0 0 5px;
            font-size: 32px; /* Slightly larger text for impact */
        }

        .carousel .caption p {
            margin: 0;
            font-size: 18px; /* Slightly larger text */
        }

        .carousel .dots {
            text-align: center;
            position: absolute;
            bottom: 12px;
            width: 100%;
        }

        .carousel .dot {
            display: inline-block;
            width: 12px;
            height: 12px;
            margin: 0 5px;
            background: rgba(255,255,255,0.6);
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s;
        }

        .carousel .dot.active,
        .carousel .dot:hover {
            background: white;
        }

        /* Footer */
        .footer {
            background: #0074cc;
            color: white;
            padding: 15px 20px 5px;
            margin-top: auto; /* Pushes footer to the bottom */
            font-size: 14px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            max-width: 1000px;
            margin: auto;
        }

        .footer-content div {
            margin-bottom: 8px;
        }

        .footer h3, .footer h4 {
            margin: 0 0 5px;
            font-size: 16px;
        }

        .footer p {
            margin: 0;
            line-height: 1.4;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 8px;
            font-size: 13px;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 6px;
        }
    </style>
</head>
<body>

<!-- ✅ Navbar -->
<div class="navbar">
    <div class="logo">e-Mart</div>
    <ul>
        <li><a href="user_home.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="user_shop.php"><i class="fas fa-store"></i> Shop</a></li>
        <li><a href="user_cart.php"><i class="fas fa-shopping-cart"></i> Cart</a></li>
        <li><a href="user_orders.php"><i class="fas fa-box"></i> Orders</a></li>
        <!-- Logout link, always available for logged-in users -->
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- 🎡 Promo Carousel -->
<div class="carousel">
    <div class="slides">
        <div class="slide">
            <img src="images/promos/promo1.png" alt="New Dress Launched">
            <div class="caption">
                <h3>New Dress Launched!</h3>
                <p>Explore our latest summer collection 🌼</p>
            </div>
        </div>
        <div class="slide">
            <img src="images/promos/promo3.png" alt="Shirt Sale">
            <div class="caption">
                <h3>Flat 50% Off on Ethnic Wear</h3>
                <p>Don't miss the trending deals 🔥</p>
            </div>
        </div>
        <div class="slide">
            <img src="images/promos/promo2.png" alt="Accessories">
            <div class="caption">
                <h3>Exclusive Collections</h3>
                <p>Complete your look with our new range ✨</p>
            </div>
        </div>
    </div>
    <div class="dots">
        <span class="dot" onclick="showSlide(0)"></span>
        <span class="dot" onclick="showSlide(1)"></span>
        <span class="dot" onclick="showSlide(2)"></span>
    </div>
</div>

<!-- 🎯 JS for Carousel -->
<script>
    let currentSlide = 0, slides, dots;
    function initCarousel() {
        slides = document.querySelectorAll('.carousel .slide');
        dots = document.querySelectorAll('.carousel .dot');
        // Ensure there are slides before trying to show them
        if (slides.length > 0) {
            showSlide(0);
            // Auto-advance carousel
            setInterval(() => showSlide(currentSlide + 1), 5000);
        }
    }
    function showSlide(n) {
        if (slides.length === 0) return; // Prevent error if no slides
        slides.forEach(s => s.style.display = 'none'); // Hide all slides
        dots.forEach(d => d.classList.remove('active')); // Deactivate all dots
        currentSlide = (n + slides.length) % slides.length; // Calculate next slide index
        slides[currentSlide].style.display = 'block'; // Show current slide
        dots[currentSlide].classList.add('active'); // Activate current dot
    }
    document.addEventListener('DOMContentLoaded', initCarousel); // Initialize carousel when DOM is ready
</script>

<!-- ✅ Footer -->
<footer class="footer">
    <div class="footer-content">
        <div>
            <h3>e-Mart</h3>
            <p>123 Fashion Street,<br>Puri, Odisha - 752001</p>
        </div>
        <div>
            <h4>Contact Us</h4>
            <p>Email: support@emart.com<br>Phone: +91 98765 43210</p>
        </div>
    </div>
    <div class="footer-bottom">
        © <?php echo date("Y"); ?> e-Mart. All rights reserved.
    </div>
</footer>

</body>
</html>
