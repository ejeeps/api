<?php
require_once __DIR__ . '/config/session.php';

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
            } elseif ($page === 'customer_service') {
                include 'view/passenger/customer_service.php';
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

$prompt_install = isset($_GET['prompt_install']) && $_GET['prompt_install'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=5.0, user-scalable=no">
    <meta name="theme-color" content="#16a34a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-JEEP">
    <meta name="description" content="E-JEEP - Modern Transportation with Cashless Payments">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/icons/icon-192x192.png">
    <title>E-JEEP - Registration Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style/index.css">
    <script src="assets/script/pwa.js"></script>
</head>
<body class="landing-app"<?php echo !empty($prompt_install) ? ' data-pwa-install-gate="1"' : ''; ?>>
    <div class="landing-shell">
        <header class="landing-header" role="banner">
            <div class="landing-brand">
                <div class="landing-logo-wrap">
                    <img src="assets/icons/icon-96x96.png" alt="" width="40" height="40" class="landing-logo" decoding="async" onerror="this.outerHTML='<span class=\'landing-logo-fallback\' aria-hidden=\'true\'>E</span>';">
                </div>
                <div class="landing-brand-text">
                    <span class="landing-app-name">E-JEEP</span>
                </div>
            </div>
            <a href="index.php?login=1" class="landing-header-login"><span class="landing-header-login__label">Log in</span></a>
        </header>

        <main class="main-content landing-main" id="main">
            <div class="landing-container">

                <section class="landing-hero" aria-labelledby="landing-title">
                    <div class="landing-hero__visual" aria-hidden="true">
                        <div class="pro-card">
                            <div class="pro-card-top">
                                <span class="pro-card-logo">E-JEEP</span>
                                <i class="fas fa-wifi pro-card-rfid"></i>
                            </div>
                            <div class="pro-card-chip"></div>
                            <div class="pro-card-bottom">
                                <div class="pro-card-dots">
                                    <span></span><span></span><span></span><span></span>
                                </div>
                                <span class="pro-card-type">TRANSIT CARD</span>
                            </div>
                        </div>
                    </div>
                    <div class="landing-hero__content">
                        <h1 id="landing-title" class="landing-hero__title">Modern Transit, Simplified.</h1>
                        <p class="landing-hero__subtitle">Experience seamless, cashless rides. Get your digital E-JEEP card today and tap your way to a better commute.</p>
                    </div>
                </section>

                <section class="landing-pick" id="register-section" aria-labelledby="pick-title">
                    <div class="section-header">
                        <h2 id="pick-title" class="landing-pick__title">Join the E-JEEP Network</h2>
                        <p class="landing-pick__hint">Choose how you’ll use E-JEEP.</p>
                    </div>

                    <div class="role-list">
                        <a href="index.php?register=passenger" class="role-row role-row--passenger">
                            <span class="role-row__icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                            <span class="role-row__body">
                                <span class="role-row__label">Passenger</span>
                                <span class="role-row__hint">Get a card and tap to pay when you board.</span>
                            </span>
                            <span class="role-row__go" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                        </a>
                        <a href="index.php?register=driver" class="role-row role-row--driver">
                            <span class="role-row__icon" aria-hidden="true"><i class="fas fa-bus"></i></span>
                            <span class="role-row__body">
                                <span class="role-row__label">Driver</span>
                                <span class="role-row__hint">Accept E-JEEP payments and manage your route.</span>
                            </span>
                            <span class="role-row__go" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                        </a>
                    </div>
                </section>

                <div class="install-strip" id="install-section" style="display: none;">
                    <div class="install-strip__row">
                        <span class="install-strip__icon" aria-hidden="true"><i class="fas fa-mobile-screen"></i></span>
                        <span class="install-strip__text">Install E-JEEP for quicker access next time.</span>
                    </div>
                    <button type="button" id="install-btn" class="install-strip__btn">
                        <i class="fas fa-download" aria-hidden="true"></i> Install
                    </button>
                </div>

                <p class="landing-login-inline">
                    <a href="index.php?login=1" class="landing-login-text">Already have an account? <strong>Log in</strong></a>
                </p>
                
                <div class="landing-legal-links">
                    <a href="index.php?page=terms">Terms</a> &middot; 
                    <a href="index.php?page=privacy">Privacy</a>
                </div>
            </div>
        </main>
    </div>

    <?php if (!empty($prompt_install)) : ?>
    <div class="pwa-install-modal" id="pwaInstallModal" role="dialog" aria-modal="true" aria-labelledby="pwaInstallTitle">
        <div class="pwa-install-modal__backdrop" data-pwa-install-dismiss tabindex="-1" aria-hidden="true"></div>
        <div class="pwa-install-modal__panel">
            <div class="pwa-install-modal__icon" aria-hidden="true"><i class="fas fa-mobile-screen-button"></i></div>
            <h2 id="pwaInstallTitle" class="pwa-install-modal__title">Install E-JEEP</h2>
            <p class="pwa-install-modal__text">Add the app to your device for faster access, registration, and your E-JEEP card.</p>
            <p class="pwa-install-modal__waiting" id="pwaInstallWaiting">Preparing install…</p>
            <div class="pwa-install-modal__actions" id="pwaInstallActions" hidden>
                <button type="button" class="pwa-install-modal__btn pwa-install-modal__btn--primary" id="pwaInstallConfirm">
                    <i class="fas fa-download" aria-hidden="true"></i> Install app
                </button>
                <button type="button" class="pwa-install-modal__btn pwa-install-modal__btn--ghost" data-pwa-install-dismiss>Not now</button>
            </div>
            <p class="pwa-install-modal__hint" id="pwaInstallHint" hidden>
                If you don’t see an install option, use your browser’s menu and choose <strong>Add to Home screen</strong> (especially on iPhone or iPad).
            </p>
        </div>
    </div>
    <?php endif; ?>

    <script src="assets/script/index/index.js"></script>
</body>
</html>
