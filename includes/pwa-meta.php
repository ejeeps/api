<?php
// Get base path for assets
$basePath = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $scriptDir = dirname($scriptPath);
    
    if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
        $basePath = '';
    } else {
        $scriptDir = trim($scriptDir, '/');
        $basePath = '/' . $scriptDir . '/';
    }
}

// Determine current page for dynamic meta data
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$pageTitle = 'E-JEEP - Modern Transportation System';
$pageDescription = 'Cashless payment system for jeepney transportation with driver and passenger management';

// Customize meta data based on current page
switch($currentPage) {
    case 'login':
        $pageTitle = 'Login - E-JEEP';
        $pageDescription = 'Login to your E-JEEP account to access your dashboard and manage your transportation needs';
        break;
    case 'driver':
        $pageTitle = 'Driver Registration - E-JEEP';
        $pageDescription = 'Register as a professional driver on the E-JEEP platform and start accepting cashless payments';
        break;
    case 'passenger':
        $pageTitle = 'Passenger Registration - E-JEEP';
        $pageDescription = 'Register as a passenger to get your E-JEEP card for convenient cashless jeepney rides';
        break;
    case 'dashboard':
        $pageTitle = 'Dashboard - E-JEEP';
        $pageDescription = 'Manage your E-JEEP account, view transactions, and access all platform features';
        break;
    case 'terms':
        $pageTitle = 'Terms of Service - E-JEEP';
        $pageDescription = 'Read the terms and conditions for using the E-JEEP transportation platform';
        break;
    case 'privacy':
        $pageTitle = 'Privacy Policy - E-JEEP';
        $pageDescription = 'Learn about how E-JEEP protects your privacy and handles your personal data';
        break;
}
?>

<!-- PWA Meta Tags -->
<meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
<meta name="keywords" content="jeepney, transportation, cashless payment, e-card, philippines, public transport">
<meta name="author" content="E-JEEP Team">

<!-- PWA Manifest -->
<link rel="manifest" href="<?php echo htmlspecialchars($basePath); ?>manifest.json">

<!-- PWA Theme Colors -->
<meta name="theme-color" content="#2563eb">
<meta name="msapplication-TileColor" content="#2563eb">
<meta name="msapplication-navbutton-color" content="#2563eb">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<!-- PWA App Configuration -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="E-JEEP">
<meta name="application-name" content="E-JEEP">

<!-- PWA Icons -->
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-180x180.png">
<link rel="apple-touch-icon" sizes="152x152" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-152x152.png">
<link rel="apple-touch-icon" sizes="144x144" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-144x144.png">
<link rel="apple-touch-icon" sizes="120x120" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-120x120.png">
<link rel="apple-touch-icon" sizes="114x114" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-114x114.png">
<link rel="apple-touch-icon" sizes="76x76" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-76x76.png">
<link rel="apple-touch-icon" sizes="72x72" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-72x72.png">
<link rel="apple-touch-icon" sizes="60x60" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-60x60.png">
<link rel="apple-touch-icon" sizes="57x57" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-57x57.png">

<!-- Microsoft Tiles -->
<meta name="msapplication-TileImage" content="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-144x144.png">
<meta name="msapplication-square70x70logo" content="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-70x70.png">
<meta name="msapplication-square150x150logo" content="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-150x150.png">
<meta name="msapplication-wide310x150logo" content="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-310x150.png">
<meta name="msapplication-square310x310logo" content="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-310x310.png">

<!-- Open Graph / Social Media -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-512x512.png">
<meta property="og:url" content="<?php echo htmlspecialchars('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>">
<meta property="og:site_name" content="E-JEEP">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
<meta name="twitter:image" content="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-512x512.png">

<!-- Preload critical resources -->
<link rel="preload" href="<?php echo htmlspecialchars($basePath); ?>assets/style/index.css" as="style">

<!-- DNS prefetch for external resources -->
<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

<script>
// Early PWA detection and setup
(function() {
    'use strict';
    
    // Set PWA display mode CSS custom property
    if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
        document.documentElement.style.setProperty('--pwa-display-mode', 'standalone');
        document.documentElement.classList.add('pwa-standalone');
    } else {
        document.documentElement.style.setProperty('--pwa-display-mode', 'browser');
        document.documentElement.classList.add('pwa-browser');
    }
    
    // Add PWA-specific classes based on device capabilities
    if ('serviceWorker' in navigator) {
        document.documentElement.classList.add('pwa-capable');
    }
    
    if ('BeforeInstallPromptEvent' in window) {
        document.documentElement.classList.add('pwa-installable');
    }
    
    // Handle viewport changes for PWA
    function handleViewportChange() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', vh + 'px');
    }
    
    handleViewportChange();
    window.addEventListener('resize', handleViewportChange);
    window.addEventListener('orientationchange', function() {
        setTimeout(handleViewportChange, 100);
    });
})();
</script>