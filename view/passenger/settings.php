<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'passenger') {
    if (!isset($dashboard_view)) {
        header("Location: ../../index.php?login=1&error=" . urlencode("Please login to access this page."));
        exit;
    } else {
        header("Location: index.php?login=1&error=" . urlencode("Please login to access this page."));
        exit;
    }
}
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controller/passenger/get_passengers_info.php';
$passengerInfo = getPassengerInfo($pdo, $_SESSION['user_id']);
if (!$passengerInfo) {
    $redirectPath = isset($dashboard_view) ? 'index.php' : '../../index.php';
    header("Location: " . $redirectPath . "?login=1&error=" . urlencode("Passenger information not found."));
    exit;
}
if (isset($dashboard_view)) {
    $basePath = '';
} else {
    $basePath = '../../';
}
$imageBasePath = $basePath;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - E-JEEP</title>
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/index.css" rel="stylesheet" type="text/css">
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/dashboard.css" rel="stylesheet" type="text/css">
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/driver.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <!-- Dashboard Header at Top -->
    <div class="dashboard-header-top">
        <div class="dashboard-header-content">
            <div class="dashboard-header-text">
                <h1 class="dashboard-title">Settings</h1>
                <p class="dashboard-subtitle">Update your profile information</p>
            </div>
            <div class="dashboard-profile-image">
                <?php if (!empty($passengerInfo['profile_image']) && file_exists($imageBasePath . $passengerInfo['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($imageBasePath . $passengerInfo['profile_image']); ?>" alt="Profile" class="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar-placeholder">
                        <?php echo strtoupper(substr($passengerInfo['first_name'], 0, 1) . substr($passengerInfo['last_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <strong>Success!</strong> Your profile has been updated successfully.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($basePath); ?>controller/passenger/PassengerUpdateController.php" method="POST" enctype="multipart/form-data" class="settings-form">
                <?php if (isset($dashboard_view)): ?>
                    <input type="hidden" name="dashboard_view" value="1">
                <?php endif; ?>
                
                <!-- Personal Information Section -->
                <div class="dashboard-section">
                    <h2 class="section-title">Personal Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" class="form-input" value="<?php echo htmlspecialchars($passengerInfo['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" class="form-input" value="<?php echo htmlspecialchars($passengerInfo['middle_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" class="form-input" value="<?php echo htmlspecialchars($passengerInfo['last_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone_number" class="form-label">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="phone_number" name="phone_number" class="form-input" value="<?php echo htmlspecialchars($passengerInfo['phone_number'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" class="form-input" value="<?php echo !empty($passengerInfo['date_of_birth']) ? htmlspecialchars($passengerInfo['date_of_birth']) : ''; ?>" max="<?php echo date('Y-m-d', strtotime('-13 years')); ?>">
                        </div>
                        <div class="form-group">
                            <label for="gender" class="form-label">Gender</label>
                            <select id="gender" name="gender" class="form-input">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (isset($passengerInfo['gender']) && $passengerInfo['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($passengerInfo['gender']) && $passengerInfo['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (isset($passengerInfo['gender']) && $passengerInfo['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Address Information Section -->
                <div class="dashboard-section">
                    <h2 class="section-title">Address Information</h2>
                    <div class="form-grid">
                        <div class="form-group form-group-full">
                            <label for="address_line1" class="form-label">Address Line 1</label>
                            <input type="text" id="address_line1" name="address_line1" class="form-input" value="<?php echo htmlspecialchars($passengerInfo['address_line1'] ?? ''); ?>">
                        </div>
                        <div class="form-group form-group-full">
                            <label for="address_line2" class="form-label">Address Line 2</label>
                            <input type="text" id="address_line2" name="address_line2" class="form-input" value="<?php echo htmlspecialchars($passengerInfo['address_line2'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="city" class="form-label">City</label>
                            <input type="text" id="city" name="city" class="form-input" value="<?php echo htmlspecialchars($passengerInfo['city'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="province" class="form-label">Province</label>
                            <input type="text" id="province" name="province" class="form-input" value="<?php echo htmlspecialchars($passengerInfo['province'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="postal_code" class="form-label">Postal Code</label>
                            <input type="text" id="postal_code" name="postal_code" class="form-input" value="<?php echo htmlspecialchars($passengerInfo['postal_code'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <!-- Profile Image Section -->
                <div class="dashboard-section">
                    <h2 class="section-title">Profile Photo</h2>
                    <div class="form-grid">
                        <div class="form-group form-group-full">
                            <?php if (!empty($passengerInfo['profile_image']) && file_exists($imageBasePath . $passengerInfo['profile_image'])): ?>
                                <div style="margin-bottom: 15px;">
                                    <label class="form-label">Current Profile Photo</label>
                                    <div style="margin-top: 10px;">
                                        <img src="<?php echo htmlspecialchars($imageBasePath . $passengerInfo['profile_image']); ?>" alt="Current Profile" style="max-width: 150px; max-height: 150px; border-radius: 8px; border: 2px solid #ddd;">
                                    </div>
                                </div>
                            <?php endif; ?>
                            <label for="profile_image" class="form-label">Update Profile Photo</label>
                            <input type="file" id="profile_image" name="profile_image" class="form-input file-input" accept="image/*">
                            <small class="form-hint">Leave empty to keep current photo (Max 5MB)</small>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                    <a href="<?php echo isset($dashboard_view) ? 'index.php' : '../../view/passenger/dashboard.php'; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <?php
    $activePage = 'settings';
    include __DIR__ . '/components/bottom_navbar.php';
    ?>
    
</body>
</html>

