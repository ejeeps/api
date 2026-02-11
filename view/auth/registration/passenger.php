<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Passenger Registration - E-JEEP</title>
    <?php
    // Get base path - works when included from index.php
    // Calculate path relative to where index.php is located
    $basePath = '';
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        // Get the directory of the script that's actually running (index.php)
        // When passenger.php is included, SCRIPT_NAME still points to index.php
        $scriptDir = dirname($scriptPath);
        
        // Handle different directory structures
        if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
            // At document root - use empty string for relative paths (like index.php)
            $basePath = '';
        } else {
            // In a subdirectory - use the directory path
            // Remove leading slash, add trailing slash
            $scriptDir = trim($scriptDir, '/');
            $basePath = '/' . $scriptDir . '/';
        }
    }
    ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath); ?>assets/style/index.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath); ?>assets/style/driver.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="<?php echo htmlspecialchars($basePath); ?>index.php">
                    <img src="<?php echo htmlspecialchars($basePath); ?>assets/logo.png" alt="E-JEEP Logo" onerror="this.outerHTML='<span class=\'logo-text\'>E-JEEP</span>';">
                </a>
            </div>
            <button class="mobile-menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="nav-links">
                <a href="<?php echo htmlspecialchars($basePath); ?>index.php" class="nav-link">Home</a>
                <a href="#" class="nav-link">Contact</a>
                <a href="<?php echo htmlspecialchars($basePath); ?>index.php?login=1" class="nav-link nav-link-login">Login</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="registration-header">
                <h1 class="registration-title">Passenger Registration</h1>
                <p class="registration-subtitle">Create your account and get your E-JEEP card for cashless payments</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Registration successful! Your account is now active. You can now <a href="<?php echo htmlspecialchars($basePath); ?>index.php?login=1">login</a>.
                </div>
            <?php endif; ?>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step-item active" data-step="1">
                    <div class="step-number">1</div>
                    <div class="step-label">Account</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="2">
                    <div class="step-number">2</div>
                    <div class="step-label">Personal</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="3">
                    <div class="step-number">3</div>
                    <div class="step-label">Address</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="4">
                    <div class="step-number">4</div>
                    <div class="step-label">ID Info</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="5">
                    <div class="step-number">5</div>
                    <div class="step-label">Profile</div>
                </div>
                <div class="step-line"></div>
                <div class="step-item" data-step="6">
                    <div class="step-number">6</div>
                    <div class="step-label">Terms</div>
                </div>
            </div>

            <form action="<?php echo htmlspecialchars($basePath); ?>controller/passenger/PassengerRegController.php" method="POST" enctype="multipart/form-data" class="registration-form" id="passengerRegistrationForm">
                
                <!-- Account Information Section -->
                <div class="form-section step-content active" data-step="1">
                    <h2 class="section-title">Account Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="password" class="form-label">Password <span class="required">*</span></label>
                            <input type="password" id="password" name="password" class="form-input" required minlength="8">
                            <small class="form-hint">Minimum 8 characters</small>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="required">*</span></label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        </div>
                    </div>
                </div>

                <!-- Personal Information Section -->
                <div class="form-section step-content" data-step="2">
                    <h2 class="section-title">Personal Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="phone_number" class="form-label">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="phone_number" name="phone_number" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="id_number" class="form-label">ID Number <span class="required">*</span></label>
                            <input type="text" id="id_number" name="id_number" class="form-input" placeholder="Government ID, Student ID, etc." required>
                            <small class="form-hint">Enter your valid ID number (Government ID, Student ID, etc.)</small>
                        </div>
                        <div class="form-group">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <div class="date-input-wrapper">
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-input date-input" max="<?php echo date('Y-m-d', strtotime('-13 years')); ?>">
                                <span class="date-icon">📅</span>
                            </div>
                            <small class="form-hint">You must be at least 13 years old</small>
                        </div>
                        <div class="form-group">
                            <label for="gender" class="form-label">Gender</label>
                            <select id="gender" name="gender" class="form-input">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Address Information Section (Optional) -->
                <div class="form-section step-content" data-step="3">
                    <h2 class="section-title">Address Information <small style="font-weight: normal; color: #666;">(Optional)</small></h2>
                    <div class="form-grid">
                        <div class="form-group form-group-full">
                            <label for="address_line1" class="form-label">Address Line 1</label>
                            <input type="text" id="address_line1" name="address_line1" class="form-input">
                        </div>
                        <div class="form-group form-group-full">
                            <label for="address_line2" class="form-label">Address Line 2</label>
                            <input type="text" id="address_line2" name="address_line2" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="city" class="form-label">City</label>
                            <input type="text" id="city" name="city" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="province" class="form-label">Province</label>
                            <input type="text" id="province" name="province" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-input">
                        </div>
                    </div>
                </div>

                <!-- ID Information Section -->
                <div class="form-section step-content" data-step="4">
                    <h2 class="section-title">ID Information</h2>
                    <div class="form-grid">
                        <div class="form-group form-group-full">
                            <label for="id_image_front" class="form-label">ID Picture - Front Side <span class="required">*</span></label>
                            <input type="file" id="id_image_front" name="id_image_front" class="form-input file-input" accept="image/*" required>
                            <small class="form-hint">Upload a clear photo of the front side of your ID (Max 5MB)</small>
                        </div>
                        <div class="form-group form-group-full">
                            <label for="id_image_back" class="form-label">ID Picture - Back Side <span class="required">*</span></label>
                            <input type="file" id="id_image_back" name="id_image_back" class="form-input file-input" accept="image/*" required>
                            <small class="form-hint">Upload a clear photo of the back side of your ID (Max 5MB)</small>
                        </div>
                    </div>
                </div>

                <!-- Profile Image Section -->
                <div class="form-section step-content" data-step="5">
                    <h2 class="section-title">Profile Photo</h2>
                    <div class="form-grid">
                        <div class="form-group form-group-full">
                            <label for="profile_image" class="form-label">Upload Profile Photo</label>
                            <input type="file" id="profile_image" name="profile_image" class="form-input file-input" accept="image/*">
                            <small class="form-hint">Optional: Upload your profile photo (Max 5MB)</small>
                        </div>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="form-section step-content" data-step="6">
                    <div class="form-group form-group-checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms" required>
                            <span>I agree to the <a href="<?php echo htmlspecialchars($basePath); ?>view/legal/terms.php" target="_blank" class="link">Terms and Conditions</a> and <a href="<?php echo htmlspecialchars($basePath); ?>view/legal/privacy.php" target="_blank" class="link">Privacy Policy</a> <span class="required">*</span></span>
                        </label>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="form-actions step-navigation">
                    <button type="button" class="btn btn-secondary btn-prev" id="prevBtn" style="display: none;">Previous</button>
                    <button type="button" class="btn btn-primary btn-next" id="nextBtn">Next</button>
                    <button type="submit" class="btn btn-primary btn-submit" id="submitBtn" style="display: none;">Submit Registration</button>
                    <a href="<?php echo htmlspecialchars($basePath); ?>index.php" class="btn btn-secondary btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

<script src="<?php echo htmlspecialchars($basePath); ?>assets/script/passenger/reg_passenger.js"></script>
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
</body>
</html>

