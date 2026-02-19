<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Login - E-JEEP</title>
    <?php
    // Get base path - works when included from index.php
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
    ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath); ?>assets/style/index.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath); ?>assets/style/login.css">
    <style>
        /* Loading state for Sign In button */
        .btn.btn-login.loading {
            opacity: 0.85;
            cursor: not-allowed;
            pointer-events: none;
        }
        .btn.btn-login .spinner {
            width: 1em;
            height: 1em;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
            vertical-align: -2px;
            animation: btn-spin 0.6s linear infinite;
        }
        @keyframes btn-spin {
            to { transform: rotate(360deg); }
        }
    </style>
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
               
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="login-container">
                <div class="login-header">
                    <h1 class="login-title">Welcome Back</h1>
                    <p class="login-subtitle">Sign in to your E-JEEP account</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['logout'])): ?>
                    <div class="alert alert-success">
                        You have been successfully logged out.
                    </div>
                <?php endif; ?>

                <form action="controller/auth/LoginController.php" method="POST" class="login-form" id="loginForm">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                        <div class="password-actions">
                            <a href="#" class="forgot-password-link">Forgot Password?</a>
                        </div>
                    </div>

                    <div class="form-group form-group-checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember_me">
                            <span>Remember me</span>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-login">Sign In</button>
                </form>

                <div class="login-footer">
                    <p>Don't have an account? 
                        <a href="index.php?register=driver" class="register-link">Register as Driver</a> or 
                        <a href="index.php?register=passenger" class="register-link">Register as Passenger</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile Menu Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const navLinks = document.querySelector('.nav-links');
            const body = document.body;

            if (mobileMenuToggle && navLinks) {
                mobileMenuToggle.addEventListener('click', function() {
                    this.classList.toggle('active');
                    navLinks.classList.toggle('active');
                    body.classList.toggle('menu-open');
                });

                // Close menu when clicking on a link
                const navLinkItems = navLinks.querySelectorAll('.nav-link');
                navLinkItems.forEach(link => {
                    link.addEventListener('click', function() {
                        mobileMenuToggle.classList.remove('active');
                        navLinks.classList.remove('active');
                        body.classList.remove('menu-open');
                    });
                });

                // Close menu when clicking outside
                document.addEventListener('click', function(event) {
                    const isClickInsideMenu = navLinks.contains(event.target);
                    const isClickOnToggle = mobileMenuToggle.contains(event.target);
                    
                    if (!isClickInsideMenu && !isClickOnToggle && navLinks.classList.contains('active')) {
                        mobileMenuToggle.classList.remove('active');
                        navLinks.classList.remove('active');
                        body.classList.remove('menu-open');
                    }
                });

                // Close menu on window resize to desktop
                window.addEventListener('resize', function() {
                    if (window.innerWidth > 768 && navLinks.classList.contains('active')) {
                        mobileMenuToggle.classList.remove('active');
                        navLinks.classList.remove('active');
                        body.classList.remove('menu-open');
                    }
                });
            }
        });
    </script>
<script>
// Sign In button loading state on submit
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('loginForm');
        if (!form) return;
        var btn = form.querySelector('.btn-login');
        if (!btn) return;

        form.addEventListener('submit', function() {
            if (btn.dataset.loading === '1') return; // prevent duplicate changes
            btn.dataset.loading = '1';
            btn.classList.add('loading');
            btn.setAttribute('aria-busy', 'true');
            btn.disabled = true;
            // Prevent layout shift by fixing width during loading
            var w = btn.offsetWidth;
            btn.style.width = w + 'px';
            btn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Signing in...</span>';
        });
    });
})();
</script>
</body>
</html>

