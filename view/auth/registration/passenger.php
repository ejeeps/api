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
    <meta name="description" content="E-JEEP Passenger Registration">
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
    <link rel="manifest" href="<?php echo htmlspecialchars($basePath); ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-192x192.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath); ?>assets/style/index.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath); ?>assets/style/driver.css">
    <script src="<?php echo htmlspecialchars($basePath); ?>assets/script/pwa.js"></script>
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
            border-radius: 0;
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
            border-radius: 0;
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
<body class="auth-app">
    <div class="auth-shell">
    <header class="auth-topbar" role="banner">
        <a href="<?php echo htmlspecialchars($basePath); ?>index.php" class="auth-back" aria-label="Back to home"><i class="fas fa-arrow-left" aria-hidden="true"></i></a>
        <div class="auth-topbar-center">
            <h1 class="auth-topbar-title">Passenger</h1>
        </div>
        <a href="<?php echo htmlspecialchars($basePath); ?>index.php?login=1" class="auth-topbar-link">Log in</a>
    </header>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
          

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

            <form action="<?php echo htmlspecialchars($basePath); ?>controller/passenger/PassengerRegController.php" method="POST" enctype="multipart/form-data" class="registration-form" id="passengerRegistrationForm" data-email-check-url="<?php echo htmlspecialchars($basePath); ?>controller/auth/CheckEmailController.php" data-ph-address-url="<?php echo htmlspecialchars($basePath); ?>controller/passenger/PhAddressController.php">
                
                <!-- Account Information Section -->
                <div class="form-section step-content active" data-step="1">
                    <h2 class="section-title">Account Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" placeholder="Input email address" class="form-input" required autocomplete="email">
                            <small id="emailAvailabilityMsg" class="form-hint email-availability-msg" hidden role="status" aria-live="polite"></small>
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
                        <div class="form-group form-group-full">
                            <label for="phone_number" class="form-label">Phone Number <span class="required">*</span></label>
                            <div class="phone-ph-field" role="group" aria-label="Philippines mobile number">
                                <span class="phone-ph-prefix" aria-hidden="true">+63</span>
                                <input type="tel" id="phone_number" name="phone_number" class="form-input phone-ph-input" placeholder="9XX XXX XXXX" inputmode="numeric" autocomplete="tel-national" required aria-describedby="phone_number_hint phone_number_error">
                            </div>
                            <small id="phone_number_hint" class="form-hint">Philippines only. Enter your 10-digit mobile number (starts with 9). Leading 0 is optional.</small>
                            <small id="phone_number_error" class="form-hint email-availability-msg email-availability-msg--error" hidden role="alert"></small>
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
                            <label for="ph_address_search" class="form-label">Search address <small style="font-weight: normal; color: #666;">(Philippines)</small></label>
                            <div class="address-search-wrap">
                                <input type="search" id="ph_address_search" class="form-input" placeholder="Street, barangay, building, city…" autocomplete="off" aria-autocomplete="list" aria-controls="ph_address_suggestions">
                                <ul id="ph_address_suggestions" class="address-suggestions" hidden role="listbox" aria-label="Address suggestions"></ul>
                            </div>
                            <small id="ph_address_source_hint" class="form-hint">Suggestions use online data when available; otherwise choose province and city below. All locations are in the Philippines.</small>
                        </div>
                        <div class="form-group form-group-full">
                            <label for="address_line1" class="form-label">Address Line 1</label>
                            <input type="text" id="address_line1" name="address_line1" class="form-input" placeholder="House / unit, street, barangay">
                        </div>
                        <div class="form-group form-group-full">
                            <label for="address_line2" class="form-label">Address Line 2</label>
                            <input type="text" id="address_line2" name="address_line2" class="form-input" placeholder="Subdivision, building (optional)">
                        </div>
                        <div class="form-group">
                            <label for="province" class="form-label">Province</label>
                            <select id="province" name="province" class="form-input form-select">
                                <option value="">Select province</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="city" class="form-label">City / municipality</label>
                            <select id="city" name="city" class="form-input form-select" disabled>
                                <option value="">Select province first</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-input" placeholder="Optional">
                        </div>
                    </div>
                </div>

                <!-- ID Information Section -->
                <div class="form-section step-content" data-step="4">
                    <h2 class="section-title">ID Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="id_type" class="form-label">ID Type <span class="required">*</span></label>
                            <select id="id_type" name="id_type" class="form-input form-select" required>
                                <option value="">Select ID type</option>
                                <option value="PhilSys National ID">PhilSys National ID</option>
                                <option value="Passport">Passport</option>
                                <option value="Driver's License">Driver's License</option>
                                <option value="UMID">UMID</option>
                                <option value="SSS ID">SSS ID</option>
                                <option value="PhilHealth ID">PhilHealth ID</option>
                                <option value="TIN ID">TIN ID</option>
                                <option value="Postal ID">Postal ID</option>
                                <option value="Voter's ID">Voter's ID</option>
                                <option value="PRC ID">PRC ID</option>
                                <option value="School ID">School ID</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group form-group-full">
                            <label for="id_number" class="form-label">ID Number <span class="required">*</span></label>
                            <input type="text" id="id_number" name="id_number" class="form-input" placeholder="Number as shown on your ID" required>
                            <small class="form-hint">Must match the ID you upload below.</small>
                        </div>
                        <div class="form-group form-group-full id-capture-block" data-target="id_image_front">
                            <div class="form-label" id="label_id_image_front">ID picture — front <span class="required">*</span></div>
                            <div class="id-capture-ui" role="region" aria-labelledby="label_id_image_front">
                                <div class="id-capture-viewport">
                                    <div class="id-capture-placeholder">
                                        <span class="id-capture-placeholder__icon" aria-hidden="true"><i class="fas fa-id-card"></i></span>
                                        <span class="id-capture-placeholder__title">Ready to capture</span>
                                        <span class="id-capture-placeholder__text">Use the camera for a clear photo of your ID, or pick an image from your gallery.</span>
                                    </div>
                                    <video class="id-capture-video" playsinline muted hidden></video>
                                    <img class="id-capture-preview" alt="Preview of ID front" hidden decoding="async">
                                </div>
                                <div class="id-capture-toolbar">
                                    <button type="button" class="btn btn-primary id-capture-btn id-capture-start" aria-label="Open camera for ID front">
                                        <i class="fas fa-camera" aria-hidden="true"></i><span>Open camera</span>
                                    </button>
                                    <button type="button" class="btn btn-primary id-capture-btn id-capture-snap" hidden aria-label="Capture ID front photo">
                                        <i class="fas fa-check" aria-hidden="true"></i><span>Capture</span>
                                    </button>
                                    <button type="button" class="btn btn-secondary id-capture-btn id-capture-retake" hidden aria-label="Retake ID front photo">
                                        <i class="fas fa-redo" aria-hidden="true"></i><span>Retake</span>
                                    </button>
                                    <button type="button" class="btn btn-secondary id-capture-btn id-capture-pick" aria-label="Choose ID front from gallery">
                                        <i class="fas fa-images" aria-hidden="true"></i><span>Gallery</span>
                                    </button>
                                </div>
                                <p class="id-capture-hint">Tip: hold the card steady in good light. Files are saved as JPEG (max 5MB on submit).</p>
                            </div>
                            <input type="file" id="id_image_front" name="id_image_front" class="id-capture-file-sr" accept="image/*" required aria-labelledby="label_id_image_front">
                        </div>
                        <div class="form-group form-group-full id-capture-block" data-target="id_image_back">
                            <div class="form-label" id="label_id_image_back">ID picture — back <span class="required">*</span></div>
                            <div class="id-capture-ui" role="region" aria-labelledby="label_id_image_back">
                                <div class="id-capture-viewport">
                                    <div class="id-capture-placeholder">
                                        <span class="id-capture-placeholder__icon" aria-hidden="true"><i class="fas fa-id-card"></i></span>
                                        <span class="id-capture-placeholder__title">Ready to capture</span>
                                        <span class="id-capture-placeholder__text">Photograph the back of the same ID, or choose from your gallery.</span>
                                    </div>
                                    <video class="id-capture-video" playsinline muted hidden></video>
                                    <img class="id-capture-preview" alt="Preview of ID back" hidden decoding="async">
                                </div>
                                <div class="id-capture-toolbar">
                                    <button type="button" class="btn btn-primary id-capture-btn id-capture-start" aria-label="Open camera for ID back">
                                        <i class="fas fa-camera" aria-hidden="true"></i><span>Open camera</span>
                                    </button>
                                    <button type="button" class="btn btn-primary id-capture-btn id-capture-snap" hidden aria-label="Capture ID back photo">
                                        <i class="fas fa-check" aria-hidden="true"></i><span>Capture</span>
                                    </button>
                                    <button type="button" class="btn btn-secondary id-capture-btn id-capture-retake" hidden aria-label="Retake ID back photo">
                                        <i class="fas fa-redo" aria-hidden="true"></i><span>Retake</span>
                                    </button>
                                    <button type="button" class="btn btn-secondary id-capture-btn id-capture-pick" aria-label="Choose ID back from gallery">
                                        <i class="fas fa-images" aria-hidden="true"></i><span>Gallery</span>
                                    </button>
                                </div>
                                <p class="id-capture-hint">Use the same lighting as the front when possible. Max 5MB on submit.</p>
                            </div>
                            <input type="file" id="id_image_back" name="id_image_back" class="id-capture-file-sr" accept="image/*" required aria-labelledby="label_id_image_back">
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
                            <span>I agree to the <button type="button" class="legal-modal-trigger" data-legal-title="Terms and Conditions" data-legal-src="<?php echo htmlspecialchars($basePath . 'index.php?page=terms', ENT_QUOTES, 'UTF-8'); ?>">Terms and Conditions</button>
                                and
                                <button type="button" class="legal-modal-trigger" data-legal-title="Privacy Policy" data-legal-src="<?php echo htmlspecialchars($basePath . 'index.php?page=privacy', ENT_QUOTES, 'UTF-8'); ?>">Privacy Policy</button>
                                <span class="required">*</span></span>
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
    </div>

    <div id="legalModal" class="legal-modal" hidden role="dialog" aria-modal="true" aria-labelledby="legalModalTitle" aria-hidden="true">
        <div class="legal-modal-backdrop" data-legal-modal-close tabindex="-1" aria-hidden="true"></div>
        <div class="legal-modal-dialog">
            <div class="legal-modal-header">
                <h2 id="legalModalTitle">Terms and Conditions</h2>
                <button type="button" class="legal-modal-close" data-legal-modal-close aria-label="Close">&times;</button>
            </div>
            <div class="legal-modal-body">
                <iframe class="legal-modal-frame" title="Legal document" src="about:blank"></iframe>
            </div>
        </div>
    </div>

<script src="<?php echo htmlspecialchars($basePath); ?>assets/script/auth/check_email_registration.js"></script>
<script src="<?php echo htmlspecialchars($basePath); ?>assets/script/auth/legal_modal_registration.js"></script>
<script src="<?php echo htmlspecialchars($basePath); ?>assets/script/passenger/ph_address_registration.js"></script>
<script src="<?php echo htmlspecialchars($basePath); ?>assets/script/passenger/id_capture_registration.js"></script>
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

</body>
</html>