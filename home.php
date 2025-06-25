<!DOCTYPE html>
<html>
<head>
    <title>e-Mart Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding-top: 60px;
            background: linear-gradient(to bottom right, #e0f7fa, #f1fdfd);
        }

        /* Navbar */
        .navbar {
    position: fixed;
    top: 0;
    width: 100%;
    background: #fff;
    border-bottom: 2px solid #f2f2f2;
    padding: 15px 50px; /* 👈 increased left-right padding */
    display: flex;
    align-items: center;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    gap: 50px; /* 👈 added space between logo and menu */
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
    margin-left: 800px; /* ✅ just slightly shifts the menu left */
}
        .navbar ul li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
        }

        .navbar ul li a:hover {
            color: #0074cc;
        }
 .navbar ul li a i {
            margin-right: 6px;
        }
        /* Carousel Styles */
        .carousel {
            position: relative;
            max-width: 1200px;
            margin: 40px auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
            height: 420px;
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
            font-size: 28px;
        }

        .carousel .caption p {
            margin: 0;
            font-size: 16px;
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
            transition: background 0.1s;
        }

        .carousel .dot.active,
        .carousel .dot:hover {
            background: white;
        }
        .footer {
    background: #0074cc;
    color: white;
    padding: 15px 20px 5px;  /* 🔽 Less vertical padding */
    margin-top: 30px;
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
        <li><a href="home.php"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="admin_upload.php"><i class="fas fa-store"></i> Products</a></li>
        <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
        <li><a href="index.php"><i class="fas fa-box"></i> Shop</a></li>
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
                <h3>Flat 50% Off on Ethenic Wear</h3>
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
        dots   = document.querySelectorAll('.carousel .dot');
        showSlide(0);
        setInterval(() => showSlide(currentSlide + 1), 5000);
    }
    function showSlide(n) {
        slides.forEach(s => s.style.display = 'none');
        dots.forEach(d => d.classList.remove('active'));
        currentSlide = (n + slides.length) % slides.length;
        slides[currentSlide].style.display = 'block';
        dots[currentSlide].classList.add('active');
    }
    document.addEventListener('DOMContentLoaded', initCarousel);
</script>

</body>
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

</html>
