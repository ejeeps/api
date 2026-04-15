<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=no">
    <meta name="theme-color" content="#16a34a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-JEEP">
    <meta name="description" content="E-JEEP - Login to your account">
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
    
    // Get base path for manifest and icons
    $manifestPath = $basePath . 'manifest.json';
    $iconPath = $basePath . 'assets/icons/icon-192x192.png';
    ?>
    <link rel="manifest" href="<?php echo htmlspecialchars($manifestPath); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($iconPath); ?>">
    <script src="<?php echo htmlspecialchars($basePath); ?>assets/script/pwa.js"></script>
    <title>Login - E-JEEP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Password input wrapper */
        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-input-wrapper .form-input {
            width: 100%;
            padding-right: 2.8rem; /* room for the eye icon */
        }
        .password-toggle-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0;
            margin: 0;
            cursor: pointer;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            line-height: 1;
        }
        .password-toggle-btn:hover,
        .password-toggle-btn:focus {
            color: #374151;
            outline: none;
        }
        .password-toggle-btn svg {
            width: 20px;
            height: 20px;
            pointer-events: none;
        }
    </style>
</head>
<body class="auth-app">
    <div class="auth-shell">
    <header class="auth-topbar" role="banner">
        <a href="<?php echo htmlspecialchars($basePath); ?>index.php" class="auth-back" aria-label="Back to home"><i class="fas fa-arrow-left" aria-hidden="true"></i></a>
        <div class="auth-topbar-center">
            <h1 class="auth-topbar-title">Log in</h1>
        </div>
        <a href="<?php echo htmlspecialchars($basePath); ?>index.php" class="auth-topbar-link">Register</a>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="login-container">
                <div class="login-header">
                    <h2 class="login-title">Welcome back</h2>
                    <p class="login-subtitle">Sign in with your email and password</p>
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

                <form action="<?php echo htmlspecialchars($basePath); ?>controller/auth/LoginController.php" method="POST" class="login-form" id="loginForm">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <!-- Password field wrapped for eye icon -->
                        <div class="password-input-wrapper">
                            <input type="password" id="password" name="password" class="form-input" required>
                            <button
                                type="button"
                                class="password-toggle-btn"
                                id="passwordToggle"
                                aria-label="Show password"
                                aria-controls="password"
                                aria-pressed="false"
                            >
                                <!-- Eye icon (shown by default = password hidden) -->
                                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <!-- Eye-off icon (shown when password is visible) -->
                                <svg id="eyeOffIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true" style="display:none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.592M9.878 9.878A3 3 0 0114.12 14.12M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
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
                    <p>Need an account? <a href="<?php echo htmlspecialchars($basePath); ?>index.php" class="register-link">Choose Passenger or Driver</a></p>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
    // Password visibility toggle
    (function () {
        document.addEventListener('DOMContentLoaded', function () {
            var toggle   = document.getElementById('passwordToggle');
            var input    = document.getElementById('password');
            var eyeOn    = document.getElementById('eyeIcon');
            var eyeOff   = document.getElementById('eyeOffIcon');

            if (!toggle || !input) return;

            toggle.addEventListener('click', function () {
                var isHidden = input.type === 'password';

                // Swap input type
                input.type = isHidden ? 'text' : 'password';

                // Swap icons
                eyeOn.style.display  = isHidden ? 'none'  : '';
                eyeOff.style.display = isHidden ? ''      : 'none';

                // Update accessibility attributes
                toggle.setAttribute('aria-label',   isHidden ? 'Hide password' : 'Show password');
                toggle.setAttribute('aria-pressed',  isHidden ? 'true'          : 'false');

                // Return focus to the input for keyboard users
                input.focus();
            });
        });
    })();
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
                if (btn.dataset.loading === '1') return;
                btn.dataset.loading = '1';
                btn.classList.add('loading');
                btn.setAttribute('aria-busy', 'true');
                btn.disabled = true;
                var w = btn.offsetWidth;
                btn.style.width = w + 'px';
                btn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Signing in...</span>';
            });
        });
    })();
    </script>
</body>
</html>