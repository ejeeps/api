<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Registration - E-JEEP</title>
    <?php
    // Get base path - works when included from index.php
    // Calculate path relative to where index.php is located
    $basePath = '';
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        // Get the directory of the script that's actually running (index.php)
        // When driver.php is included, SCRIPT_NAME still points to index.php
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
                <h1 class="registration-title">Driver Registration</h1>
                <p class="registration-subtitle">Create your account and apply to become an E-JEEP driver</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Registration successful! Your application is pending approval. You can now <a href="#">login</a>.
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
                    <div class="step-label">License</div>
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

            <form action="<?php echo htmlspecialchars($basePath); ?>controller/driver/DriverRegController.php" method="POST" enctype="multipart/form-data" class="registration-form" id="driverRegistrationForm">
                
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
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <div class="date-input-wrapper">
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-input date-input" max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                                <span class="date-icon">📅</span>
                            </div>
                            <small class="form-hint">You must be at least 18 years old</small>
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

                <!-- Address Information Section -->
                <div class="form-section step-content" data-step="3">
                    <h2 class="section-title">Address Information</h2>
                    <div class="form-grid">
                        <div class="form-group form-group-full">
                            <label for="address_line1" class="form-label">Address Line 1 <span class="required">*</span></label>
                            <input type="text" id="address_line1" name="address_line1" class="form-input" required>
                        </div>
                        <div class="form-group form-group-full">
                            <label for="address_line2" class="form-label">Address Line 2</label>
                            <input type="text" id="address_line2" name="address_line2" class="form-input">
                        </div>
                        <div class="form-group">
                            <label for="city" class="form-label">City <span class="required">*</span></label>
                            <input type="text" id="city" name="city" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="province" class="form-label">Province <span class="required">*</span></label>
                            <input type="text" id="province" name="province" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label for="postal_code" class="form-label">Postal Code <span class="required">*</span></label>
                            <input type="text" id="postal_code" name="postal_code" class="form-input" required>
                        </div>
                    </div>
                </div>

                <!-- Driver's License Information Section -->
                <div class="form-section step-content" data-step="4">
                    <h2 class="section-title">Driver's License Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="license_number" class="form-label">License Number <span class="required">*</span></label>
                            <input type="text" id="license_number" name="license_number" class="form-input" placeholder="HO3-25-005483 (XXX-YY-ZZZZZZ)" required>
                        </div>
                        <div class="form-group">
                            <label for="license_type" class="form-label">License Type <span class="required">*</span></label>
                            <select id="license_type" name="license_type" class="form-input form-select" required>
                                <option value="">Select License Type</option>
                                <option value="Student Permit">Student Permit</option>
                                <option value="Non-Professional">Non-Professional</option>
                                <option value="Professional">Professional</option>
                                <option value="Conductor's License">Conductor's License</option>
                                <option value="Restricted">Restricted</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="license_expiry_date" class="form-label">License Expiry Date <span class="required">*</span></label>
                            <div class="date-input-wrapper">
                                <input type="date" id="license_expiry_date" name="license_expiry_date" class="form-input date-input" required min="<?php echo date('Y-m-d'); ?>">
                                <span class="date-icon">📅</span>
                            </div>
                            <small class="form-hint">Select your license expiration date</small>
                        </div>
                        <div class="form-group form-group-full">
                            <label for="license_image" class="form-label">Driver's License - Front Side <span class="required">*</span></label>
                            <input type="file" id="license_image" name="license_image" class="form-input file-input" accept="image/*" required>
                            <small class="form-hint">Upload a clear photo of the front side of your driver's license (Max 5MB)</small>
                        </div>
                        <div class="form-group form-group-full">
                            <label for="license_back_image" class="form-label">Driver's License - Back Side <span class="required">*</span></label>
                            <input type="file" id="license_back_image" name="license_back_image" class="form-input file-input" accept="image/*" required>
                            <small class="form-hint">Upload a clear photo of the back side of your driver's license (Max 5MB)</small>
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
                    <div class="form-group form-group-checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" name="background_check" required>
                            <span>I understand that a background check is required for driver approval <span class="required">*</span></span>
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

<script src="<?php echo htmlspecialchars($basePath); ?>assets/script/driver/reg_driver.js"></script>
</body>
</html>
