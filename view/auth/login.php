<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                    <img src="assets/logo.png" alt="E-JEEP Logo" onerror="this.outerHTML='<span class=\'logo-text\'>E-JEEP</span>';">
            </div>
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
</body>
</html>

