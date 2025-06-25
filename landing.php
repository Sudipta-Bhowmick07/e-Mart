<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-Mart</title>
    <!-- Tailwind CSS CDN for modern styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Font: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Custom CSS to blend with Tailwind, focusing on overall layout and specific elements */
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(to bottom right, #e0f7fa, #f1fdfd);

        }
        .hero-section {
            background: linear-gradient(to bottom right, #e0f7fa, #f1fdfd);
            min-height: 100vh; /* Full viewport height */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative; /* For the shop name positioning */
            overflow: hidden; /* Hide any overflowing content, especially for the image */
        }
        .hero-content {
            max-width: 1200px;
            width: 100%;
            display: flex;
            flex-direction: column; /* Stack elements on small screens */
            align-items: flex-start; /* Align text to start on small screens */
            gap: 2rem;
            position: relative;
            z-index: 10; /* Ensure content is above any background effects */
        }
        /* Responsive adjustments for larger screens (md breakpoint: 768px) */
        @media (min-width: 768px) {
            .hero-content {
                flex-direction: row; /* Side-by-side on larger screens */
                align-items: center; /* Vertically center content */
                justify-content: space-between; /* Space out text and image */
                text-align: left; /* Align text to left */
            }
            .hero-text {
                flex: 1; /* Allow text to take available space */
                max-width: 50%; /* Limit text width */
            }
            .hero-image {
                flex: 1; /* Allow image to take available space */
                max-width: 50%; /* Limit image width */
            }
        }
        .shop-name {
            position: absolute;
            top: 2rem;
            left: 2rem;
            font-size: 2.5rem;
            font-weight: 700;
            color: #0074cc; /* Red color from original image logo */
            z-index: 20; /* Ensure shop name is on top */
            border-radius: 0.5rem; /* Slightly rounded corners for the text area */
        }
        .login-button {
            background-color: #0074cc; /* Red color for button */
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px; /* Fully rounded pill shape */
            font-weight: 600;
            transition: background-color 0.3s ease; /* Smooth hover effect */
        }
        .login-button:hover {
            background-color: #dc2626; /* Darker red on hover */
        }
    </style>
</head>
<body>

    <!-- Shop Name positioned on the top left corner -->
    <div class="shop-name">e-Mart</div>

    <!-- Hero Section: Main content area for landing page -->
    <section class="hero-section">
        <div class="hero-content">
            <!-- Left Text Content for the hero section -->
            <div class="hero-text p-4 md:p-0">
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-800 leading-tight mb-6">
                    Give Your Daily Life <br> A New Style!
                </h1>
                <p class="text-lg md:text-xl text-gray-600 mb-8 max-w-lg">
                    Fashion that fits, style that sticks – shop the look everyone’s talking about!<br>
                <a href="login.php" class="login-button inline-block shadow-lg">Login to Explore &#8594;</a>
            </div>

            <!-- Right Image Content for the hero section -->
            <div class="hero-image p-4 md:p-0">
                <!-- Placeholder image: Replace with your actual image file -->
                <img src="images/S.png" alt="Workout Style" class="w-full h-auto object-contain rounded-lg shadow-xl">
            </div>
        </div>
    </section>

</body>
</html>
