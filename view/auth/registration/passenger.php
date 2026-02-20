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
    <style>
        /* Loading state for Submit Registration button */
        .btn.btn-submit.loading {
            opacity: 0.85;
            cursor: not-allowed;
            pointer-events: none;
        }
        .btn.btn-submit .spinner {
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

        /* ── Password visibility toggle ── */
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper .form-input {
            /* leave room for the toggle button */
            padding-right: 2.75rem;
            width: 100%;
            box-sizing: border-box;
        }
        .password-toggle {
            position: absolute;
            right: 0.65rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0.25rem;
            cursor: pointer;
            color: #6b7280;
            line-height: 0;
            border-radius: 0.25rem;
            transition: color 0.15s ease;
            flex-shrink: 0;
        }
        .password-toggle:hover,
        .password-toggle:focus-visible {
            color: #111827;
            outline: 2px solid currentColor;
            outline-offset: 2px;
        }
        .password-toggle svg {
            width: 1.2rem;
            height: 1.2rem;
            pointer-events: none;
        }
        /* hide the "eye-off" icon by default; show when data-visible="true" */
        .password-toggle .icon-eye-off { display: none; }
        .password-toggle[data-visible="true"] .icon-eye     { display: none; }
        .password-toggle[data-visible="true"] .icon-eye-off { display: block; }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
               <span class="logo-text">E-JEEP</span>
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
                            <input type="email" id="email" name="email" placeholder="Input email address" class="form-input" required>
                        </div>

                        <!-- Password with eye toggle -->
                        <div class="form-group">
                            <label for="password" class="form-label">Password <span class="required">*</span></label>
                            <div class="password-wrapper">
                                <input type="password" id="password" placeholder="Input password" name="password" class="form-input" required minlength="8" autocomplete="new-password">
                                <button type="button"
                                        class="password-toggle"
                                        aria-label="Show password"
                                        aria-controls="password"
                                        data-visible="false">
                                    <!-- Eye icon (password hidden) -->
                                    <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         aria-hidden="true" focusable="false">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <!-- Eye-off icon (password visible) -->
                                    <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         aria-hidden="true" focusable="false">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
                            <small class="form-hint">Minimum 8 characters</small>
                        </div>

                        <!-- Confirm Password with eye toggle -->
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="required">*</span></label>
                            <div class="password-wrapper">
                                <input type="password" placeholder="Input confirmed password" id="confirm_password" name="confirm_password" class="form-input" required autocomplete="new-password">
                                <button type="button"
                                        class="password-toggle"
                                        aria-label="Show confirm password"
                                        aria-controls="confirm_password"
                                        data-visible="false">
                                    <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         aria-hidden="true" focusable="false">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         aria-hidden="true" focusable="false">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                </button>
                            </div>
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

<!-- ── Password visibility toggle (Register page only) ── -->
<script>
(function () {
    'use strict';

    /**
     * Toggles a single password field between hidden and visible,
     * and keeps the toggle button's aria-label + data-visible in sync.
     *
     * @param {HTMLButtonElement} btn   The toggle button element
     */
    function initPasswordToggle(btn) {
        var targetId = btn.getAttribute('aria-controls');
        var input    = targetId ? document.getElementById(targetId) : null;

        if (!input) {
            console.warn('[password-toggle] No input found for aria-controls="' + targetId + '"');
            return;
        }

        btn.addEventListener('click', function () {
            var isVisible = btn.getAttribute('data-visible') === 'true';

            if (isVisible) {
                // Hide the password
                input.type = 'password';
                btn.setAttribute('data-visible', 'false');
                btn.setAttribute('aria-label', btn.getAttribute('aria-label').replace('Hide', 'Show'));
            } else {
                // Reveal the password
                input.type = 'text';
                btn.setAttribute('data-visible', 'true');
                btn.setAttribute('aria-label', btn.getAttribute('aria-label').replace('Show', 'Hide'));
            }

            // Return focus to the input so screen-reader users hear the updated value
            input.focus();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var toggles = document.querySelectorAll('#passengerRegistrationForm .password-toggle');
        toggles.forEach(function (btn) {
            initPasswordToggle(btn);
        });
    });
}());
</script>

<!-- ── Submit button loading state ── -->
<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('passengerRegistrationForm');
        if (!form) return;
        var btn = document.getElementById('submitBtn');
        if (!btn) return;

        form.addEventListener('submit', function() {
            if (btn.dataset.loading === '1') return;
            btn.dataset.loading = '1';
            btn.classList.add('loading');
            btn.setAttribute('aria-busy', 'true');
            btn.disabled = true;
            var w = btn.offsetWidth;
            btn.style.width = w + 'px';
            btn.innerHTML = '<span class="spinner" aria-hidden="true"></span><span>Processing...</span>';
        });
    });
})();
</script>

<!-- ── Mobile Menu Toggle ── -->
<script>
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

            const navLinkItems = navLinks.querySelectorAll('.nav-link');
            navLinkItems.forEach(link => {
                link.addEventListener('click', function() {
                    mobileMenuToggle.classList.remove('active');
                    navLinks.classList.remove('active');
                    body.classList.remove('menu-open');
                });
            });

            document.addEventListener('click', function(event) {
                const isClickInsideMenu = navLinks.contains(event.target);
                const isClickOnToggle = mobileMenuToggle.contains(event.target);
                
                if (!isClickInsideMenu && !isClickOnToggle && navLinks.classList.contains('active')) {
                    mobileMenuToggle.classList.remove('active');
                    navLinks.classList.remove('active');
                    body.classList.remove('menu-open');
                }
            });

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