<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_level'] === 'driver') {
        // Check which page is requested
        if (isset($_GET['page'])) {
            $dashboard_view = 'driver';
            $page = $_GET['page'];
            
            if ($page === 'settings') {
                include 'view/driver/settings.php';
                exit;
            }
        }
        // Include driver dashboard view instead of redirecting
        $dashboard_view = 'driver';
        include 'view/driver/dashboard.php';
        exit;
    } else if ($_SESSION['user_level'] === 'passenger') {
        // Check which page is requested
        if (isset($_GET['page'])) {
            $dashboard_view = 'passenger';
            $page = $_GET['page'];
            
            if ($page === 'settings') {
                include 'view/passenger/settings.php';
                exit;
            } elseif ($page === 'transaction') {
                include 'view/passenger/transaction.php';
                exit;
            } elseif ($page === 'buypoints') {
                include 'view/passenger/buypoints.php';
                exit;
            }
        }
        // Include passenger dashboard view instead of redirecting
        $dashboard_view = 'passenger';
        include 'view/passenger/dashboard.php';
        exit;
    }
}

// Check if login is requested
if (isset($_GET['login']) && $_GET['login'] === '1') {
    include 'view/auth/login.php';
    exit;
}

// Check if legal pages are requested
if (isset($_GET['page'])) {
    $page = $_GET['page'];
    if ($page === 'terms') {
        include 'view/legal/terms.php';
        exit;
    } elseif ($page === 'privacy') {
        include 'view/legal/privacy.php';
        exit;
    }
}

// Check if registration type is specified
$register = isset($_GET['register']) ? $_GET['register'] : '';

// If driver registration is requested, include the driver registration page
if ($register === 'driver') {
    include 'view/auth/registration/driver.php';
    exit;
}

// If passenger registration is requested, include the passenger registration page
if ($register === 'passenger') {
    include 'view/auth/registration/passenger.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>E-JEEP - Registration Portal</title>
    <link rel="stylesheet" href="assets/style/index.css">
  

</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="assets/logo.png" alt="E-JEEP Logo" onerror="this.outerHTML='<span class=\'logo-text\'>E-JEEP</span>';">
            </div>
            <button class="mobile-menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-links">
                <a href="index.php" class="nav-link">Home</a>
               
                <a href="#" class="nav-link">Contact</a>
                <a href="index.php?login=1" class="nav-link nav-link-login">Login</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="page-header">
               
                <p class="page-description">
                    Welcome to E-JEEP Registration Portal. Whether you're a driver operating on routes 
                    or a passenger wanting to get your E-JEEP card for cashless payments when boarding, 
                    we've got you covered. Choose your registration type below.
                </p>
            </div>

            <!-- Tag List Scroller -->
            <div class="taglist-scroller-container">
                <div class="taglist-scroller">
                    <div class="tag-item">🚌 Cashless Payment</div>
                    <div class="tag-item">💳 E-JEEP Card</div>
                    <div class="tag-item">⚡ Quick Transactions</div>
                    <div class="tag-item">🔒 Secure System</div>
                    <div class="tag-item">📱 Easy Registration</div>
                    <div class="tag-item">🎯 Convenient</div>
                    <div class="tag-item">💰 Reloadable Balance</div>
                    <div class="tag-item">✅ Verified Accounts</div>
                    <div class="tag-item">🛣️ Route Management</div>
                    <div class="tag-item">📊 Track Transactions</div>
                </div>
            </div>

        <div class="registration-types">
            <!-- Driver Registration Card -->
            <div class="registration-card">
                <div class="registration-icon">🚌</div>
                <h2 class="registration-title">Driver Registration</h2>
                <p class="registration-description">
                    Join our platform as a professional driver and operate on your designated routes. 
                    Get your E-JEEP card and accept card payments from passengers as they board your jeepney.
                </p>
                
                <div class="registration-benefits">
                    <p class="registration-benefits-title">Benefits:</p>
                    <ul class="benefits-list">
                        <li>Get your E-JEEP card</li>
                        <li>Accept E-JEEP card payments from passengers</li>
                        <li>Manage your routes and schedule</li>
                        <li>Cashless transaction system</li>
                        <li>Secure payment processing</li>
                        <li>Verified driver profile</li>
                    </ul>
                </div>

                <div class="card-highlight">
                    🎫 Get your E-JEEP Card after registration! Requirements: Valid Driver's License, Background Check
                </div>

                <a href="index.php?register=driver" class="btn btn-primary btn-register">Register as Driver</a>
            </div>

            <!-- Passenger Registration Card -->
            <div class="registration-card">
                <div class="registration-icon">👤</div>
                <h2 class="registration-title">Passenger Registration</h2>
                <p class="registration-description">
                    Register as a passenger to get your E-JEEP card. Simply tap your card when you enter 
                    the jeepney for quick and cashless payment. No need to carry exact change!
                </p>
                
                <div class="registration-benefits">
                    <p class="registration-benefits-title">Benefits:</p>
                    <ul class="benefits-list">
                        <li>Get your E-JEEP card</li>
                        <li>Tap card when entering jeepney</li>
                        <li>Cashless payment - no exact change needed</li>
                        <li>Quick and convenient transactions</li>
                        <li>Reloadable card balance</li>
                    </ul>
                </div>

                <div class="card-highlight">
                    🎫 Get your E-JEEP Card after registration! Just tap when you board any jeepney on the route.
                </div>

                <a href="index.php?register=passenger" class="btn btn-primary btn-register">Register as Passenger</a>
            </div>
        </div>

            <div class="login-link">
                Already have an account? <a href="index.php?login=1" class="login-link-text">Login here</a>
            </div>
        </div>
    </div>
    <script src="assets/script/index/index.js"></script>
</body>
</html>