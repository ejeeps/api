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
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="E-JEEP - Modern cashless payment system for jeepney transportation. Register as driver or passenger for convenient, secure transactions.">
    <meta name="keywords" content="jeepney, transportation, cashless payment, e-card, philippines, public transport">
    <meta name="author" content="E-JEEP Team">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- PWA Theme Colors -->
    <meta name="theme-color" content="#2563eb">
    <meta name="msapplication-TileColor" content="#2563eb">
    <meta name="msapplication-navbutton-color" content="#2563eb">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- PWA App Configuration -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="E-JEEP">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="application-name" content="E-JEEP">
    
    <!-- Mobile Specific PWA Meta Tags -->
    <meta name="format-detection" content="telephone=no">
    <meta name="msapplication-tap-highlight" content="no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/icons/icon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/icons/icon-180x180.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="144x144" href="assets/icons/icon-144x144.png">
    <link rel="apple-touch-icon" sizes="120x120" href="assets/icons/icon-120x120.png">
    <link rel="apple-touch-icon" sizes="114x114" href="assets/icons/icon-114x114.png">
    <link rel="apple-touch-icon" sizes="76x76" href="assets/icons/icon-76x76.png">
    <link rel="apple-touch-icon" sizes="72x72" href="assets/icons/icon-72x72.png">
    <link rel="apple-touch-icon" sizes="60x60" href="assets/icons/icon-60x60.png">
    <link rel="apple-touch-icon" sizes="57x57" href="assets/icons/icon-57x57.png">
    
    <!-- Microsoft Tiles -->
    <meta name="msapplication-TileImage" content="assets/icons/icon-144x144.png">
    <meta name="msapplication-square70x70logo" content="assets/icons/icon-70x70.png">
    <meta name="msapplication-square150x150logo" content="assets/icons/icon-150x150.png">
    <meta name="msapplication-wide310x150logo" content="assets/icons/icon-310x150.png">
    <meta name="msapplication-square310x310logo" content="assets/icons/icon-310x310.png">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="E-JEEP - Modern Transportation System">
    <meta property="og:description" content="Cashless payment system for jeepney transportation with driver and passenger management">
    <meta property="og:image" content="assets/icons/icon-512x512.png">
    <meta property="og:url" content="https://yoursite.com/api/">
    <meta property="og:site_name" content="E-JEEP">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="E-JEEP - Modern Transportation System">
    <meta name="twitter:description" content="Cashless payment system for jeepney transportation">
    <meta name="twitter:image" content="assets/icons/icon-512x512.png">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <!-- Hero Jeepney Image -->
            <div class="page-header">
                <!-- Pure jeepney background image display -->
            </div>

            <!-- Tag List Scroller -->
            <div class="taglist-scroller-container">
                <div class="taglist-scroller">
                    <div class="tag-item">
                        <i class="fas fa-credit-card tag-icon"></i>
                        <span class="tag-text">Cashless Payment</span>
                    </div>
                    <div class="tag-item">
                        <i class="fas fa-id-card tag-icon"></i>
                        <span class="tag-text">E-JEEP Card</span>
                    </div>
                    <div class="tag-item">
                        <i class="fas fa-bolt tag-icon"></i>
                        <span class="tag-text">Quick Transactions</span>
                    </div>
                    <div class="tag-item">
                        <i class="fas fa-shield-alt tag-icon"></i>
                        <span class="tag-text">Secure System</span>
                    </div>
                    <div class="tag-item">
                        <i class="fas fa-user-plus tag-icon"></i>
                        <span class="tag-text">Easy Registration</span>
                    </div>
                    <div class="tag-item">
                        <i class="fas fa-star tag-icon"></i>
                        <span class="tag-text">Convenient</span>
                    </div>
                    <div class="tag-item">
                        <i class="fas fa-wallet tag-icon"></i>
                        <span class="tag-text">Reloadable Balance</span>
                    </div>
                    <div class="tag-item">
                        <i class="fas fa-check-circle tag-icon"></i>
                        <span class="tag-text">Verified Accounts</span>
                    </div>
                    <div class="tag-item">
                        <i class="fas fa-route tag-icon"></i>
                        <span class="tag-text">Route Management</span>
                    </div>
                    <div class="tag-item">
                        <i class="fas fa-chart-bar tag-icon"></i>
                        <span class="tag-text">Track Transactions</span>
                    </div>
                </div>
            </div>

            <!-- Description Section -->
            <div class="description-section">
                <p class="page-description">
                    Welcome to E-JEEP Registration Portal. Whether you're a driver operating on routes 
                    or a passenger wanting to get your E-JEEP card for cashless payments when boarding, 
                    we've got you covered. Choose your registration type below.
                </p>
            </div>


        <div class="registration-types">
            <!-- Driver Registration Card -->
            <div class="registration-card">
                <div class="registration-icon">
                    <i class="fas fa-bus"></i>
                </div>
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
                <div class="registration-icon">
                    <i class="fas fa-user"></i>
                </div>
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

            <!-- Login Section -->
            <div class="login-section">
                <div class="login-card">
                    <div class="login-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <h3 class="login-title">Welcome Back!</h3>
                    <p class="login-description">Already have an E-JEEP account? Access your dashboard to manage your card, view transactions, and more.</p>
                    <a href="index.php?login=1" class="btn btn-secondary btn-login">Login to Your Account</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Simple Mobile Install Instructions -->
    <div class="simple-install-section">
        <div class="container">
            <div class="install-instruction">
                <div class="install-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <div class="install-text">
                    <h3>📱 Install E-JEEP as Mobile App</h3>
                    <p><strong>Android:</strong> Use install button below or Chrome address bar | <strong>iPhone:</strong> Share → Add to Home Screen</p>
                </div>
                <div class="install-button-container">
                    <button id="install-app-btn" class="install-app-button">
                        <i class="fas fa-download"></i>
                        Install App
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-text">
                    <h3>E-JEEP</h3>
                    <p>Modern Transportation, Cashless Payments</p>
                    <p>&copy; 2026 E-JEEP. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- PWA Install Prompt -->
    <div id="pwa-install-prompt" class="pwa-install-prompt" style="display: none;">
        <div class="pwa-install-content">
            <div class="pwa-install-icon">
                <i class="fas fa-download"></i>
            </div>
            <div class="pwa-install-text">
                <h3>Install E-JEEP App</h3>
                <p>Get the full app experience with offline access and faster loading!</p>
            </div>
            <div class="pwa-install-actions">
                <button id="pwa-install-button" class="btn btn-primary">Install</button>
                <button id="pwa-install-dismiss" class="btn btn-secondary">Not Now</button>
            </div>
        </div>
    </div>

    <script src="assets/script/index/index.js"></script>
    
    <!-- PWA Service Worker Registration -->
    <script>
        // PWA Service Worker Registration (for installation support only)
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('./sw.js', {
                    scope: './'
                })
                .then(registration => {
                    console.log('SW registered for PWA installation: ', registration);
                })
                .catch(registrationError => {
                    console.log('SW registration failed: ', registrationError);
                });
            });
        }

        // PWA Install Prompt
        let deferredPrompt;
        const installPrompt = document.getElementById('pwa-install-prompt');
        const installButton = document.getElementById('pwa-install-button');
        const dismissButton = document.getElementById('pwa-install-dismiss');
        const footerInstallBtn = document.getElementById('install-app-btn');

        // Check if app is already installed
        function isAppInstalled() {
            return window.matchMedia('(display-mode: standalone)').matches || 
                   window.navigator.standalone === true;
        }

        // Update install button visibility
        function updateInstallButton() {
            if (footerInstallBtn) {
                if (isAppInstalled()) {
                    footerInstallBtn.style.display = 'none';
                } else {
                    footerInstallBtn.style.display = 'inline-flex';
                    footerInstallBtn.innerHTML = '<i class="fas fa-download"></i> Install App';
                }
            }
        }

        // Listen for the beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA install prompt triggered');
            // Prevent Chrome 67 and earlier from automatically showing the prompt
            e.preventDefault();
            // Stash the event so it can be triggered later
            deferredPrompt = e;
            
            // Update button to show it's ready for install
            if (footerInstallBtn) {
                footerInstallBtn.innerHTML = '<i class="fas fa-download"></i> Install App';
                footerInstallBtn.style.display = 'inline-flex';
            }
            
            // Show custom install prompt after a delay (only if footer button not clicked)
            setTimeout(() => {
                if (deferredPrompt) {
                    showInstallPrompt();
                }
            }, 5000);
        });

        // Initialize button state on page load
        window.addEventListener('load', () => {
            updateInstallButton();
        });

        // Show install prompt
        function showInstallPrompt() {
            if (deferredPrompt && !localStorage.getItem('pwa-install-dismissed')) {
                installPrompt.style.display = 'block';
                installPrompt.style.animation = 'slideInUp 0.3s ease-out';
            }
        }

        // Handle main install button click
        installButton.addEventListener('click', () => {
            hideInstallPrompt();
            triggerInstall();
        });

        // Handle footer install button click
        if (footerInstallBtn) {
            footerInstallBtn.addEventListener('click', () => {
                triggerInstall();
            });
        }

        // Trigger install function
        function triggerInstall() {
            // Update button to show installing state
            if (footerInstallBtn) {
                footerInstallBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Installing...';
                footerInstallBtn.disabled = true;
            }
            
            if (deferredPrompt) {
                // Automatically trigger the install prompt
                deferredPrompt.prompt();
                
                // Wait for the user to respond to the prompt
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                        
                        // Show success state
                        if (footerInstallBtn) {
                            footerInstallBtn.innerHTML = '<i class="fas fa-check"></i> Installed!';
                            footerInstallBtn.style.background = '#10b981';
                            
                            // Hide button after 2 seconds
                            setTimeout(() => {
                                footerInstallBtn.style.display = 'none';
                                // Hide the entire install section
                                const installSection = document.querySelector('.simple-install-section');
                                if (installSection) {
                                    installSection.style.display = 'none';
                                }
                            }, 2000);
                        }
                        
                        // Show success notification
                        showInstallSuccessNotification();
                        
                    } else {
                        console.log('User dismissed the install prompt');
                        // Reset button state
                        if (footerInstallBtn) {
                            footerInstallBtn.innerHTML = '<i class="fas fa-download"></i> Install App';
                            footerInstallBtn.disabled = false;
                            footerInstallBtn.style.background = 'var(--primary-color)';
                        }
                    }
                    deferredPrompt = null;
                });
            } else {
                // Check if already installed
                if (isAppInstalled()) {
                    if (footerInstallBtn) {
                        footerInstallBtn.innerHTML = '<i class="fas fa-check"></i> Already Installed';
                        footerInstallBtn.style.background = '#10b981';
                        footerInstallBtn.disabled = true;
                    }
                    showAlreadyInstalledNotification();
                } else {
                    // Show automatic installation guide
                    showInstallationGuide();
                    // Reset button state
                    if (footerInstallBtn) {
                        footerInstallBtn.innerHTML = '<i class="fas fa-download"></i> Install App';
                        footerInstallBtn.disabled = false;
                        footerInstallBtn.style.background = 'var(--primary-color)';
                    }
                }
            }
        }
        
        // Show success notification
        function showInstallSuccessNotification() {
            const notification = document.createElement('div');
            notification.className = 'install-notification success';
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-check-circle"></i>
                    <div class="notification-text">
                        <strong>🎉 E-JEEP Installed Successfully!</strong>
                        <p>Look for the E-JEEP icon on your home screen</p>
                    </div>
                </div>
            `;
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
        
        // Show already installed notification
        function showAlreadyInstalledNotification() {
            const notification = document.createElement('div');
            notification.className = 'install-notification info';
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-mobile-alt"></i>
                    <div class="notification-text">
                        <strong>📱 E-JEEP is Already Installed</strong>
                        <p>Check your home screen or app drawer</p>
                    </div>
                </div>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 4000);
        }
        
        // Show installation guide
        function showInstallationGuide() {
            const userAgent = navigator.userAgent.toLowerCase();
            let title = '';
            let instructions = '';
            
            if (userAgent.includes('chrome') && userAgent.includes('android')) {
                title = '📱 Install E-JEEP on Android';
                instructions = `
                    <div class="guide-steps">
                        <div class="guide-step">
                            <span class="step-number">1</span>
                            <span>Look for the install icon (⬇️) in Chrome address bar</span>
                        </div>
                        <div class="guide-step">
                            <span class="step-number">2</span>
                            <span>Tap it and confirm installation</span>
                        </div>
                        <div class="guide-alternative">
                            <strong>Alternative:</strong> Menu (⋮) → "Add to Home Screen"
                        </div>
                    </div>
                `;
            } else if (userAgent.includes('safari') && (userAgent.includes('iphone') || userAgent.includes('ipad'))) {
                title = '📱 Install E-JEEP on iPhone/iPad';
                instructions = `
                    <div class="guide-steps">
                        <div class="guide-step">
                            <span class="step-number">1</span>
                            <span>Tap the Share button (📤) at the bottom</span>
                        </div>
                        <div class="guide-step">
                            <span class="step-number">2</span>
                            <span>Select "Add to Home Screen"</span>
                        </div>
                        <div class="guide-step">
                            <span class="step-number">3</span>
                            <span>Tap "Add" to confirm</span>
                        </div>
                    </div>
                `;
            } else {
                title = '📱 Install E-JEEP as App';
                instructions = `
                    <div class="guide-steps">
                        <div class="guide-step">
                            <span class="step-number">📱</span>
                            <span><strong>Android Chrome:</strong> Look for install icon in address bar</span>
                        </div>
                        <div class="guide-step">
                            <span class="step-number">🍎</span>
                            <span><strong>iPhone Safari:</strong> Share → Add to Home Screen</span>
                        </div>
                    </div>
                `;
            }
            
            const modal = document.createElement('div');
            modal.className = 'install-modal';
            modal.innerHTML = `
                <div class="modal-overlay" onclick="closeInstallModal()"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>${title}</h3>
                        <button class="modal-close" onclick="closeInstallModal()">×</button>
                    </div>
                    <div class="modal-body">
                        ${instructions}
                    </div>
                    <div class="modal-footer">
                        <button class="modal-btn" onclick="closeInstallModal()">Got it!</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            // Auto close after 10 seconds
            setTimeout(() => {
                if (document.body.contains(modal)) {
                    modal.remove();
                }
            }, 10000);
        }
        
        // Close install modal
        function closeInstallModal() {
            const modal = document.querySelector('.install-modal');
            if (modal) {
                modal.remove();
            }
        }

        // Handle dismiss button click
        dismissButton.addEventListener('click', () => {
            hideInstallPrompt();
            localStorage.setItem('pwa-install-dismissed', 'true');
        });

        // Hide install prompt
        function hideInstallPrompt() {
            installPrompt.style.animation = 'slideOutDown 0.3s ease-in';
            setTimeout(() => {
                installPrompt.style.display = 'none';
            }, 300);
        }


        // Listen for app installation
        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed');
            hideInstallPrompt();
            
            // Show success message
            const successMsg = document.createElement('div');
            successMsg.className = 'pwa-install-success';
            successMsg.innerHTML = `
                <div class="success-content">
                    <i class="fas fa-check-circle"></i>
                    <span>E-JEEP installed successfully!</span>
                </div>
            `;
            document.body.appendChild(successMsg);
            
            setTimeout(() => {
                successMsg.remove();
            }, 3000);
        });
    </script>
</body>
</html>