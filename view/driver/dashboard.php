<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'driver') {
    $redirectPath = isset($dashboard_view) ? 'index.php' : '../../index.php';
    header("Location: " . $redirectPath . "?login=1&error=" . urlencode("Please login to access this page."));
    exit;
}

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../controller/driver/get_drivers_info.php';

$driverInfo = getDriverInfo($pdo, $_SESSION['user_id']);

if (!$driverInfo) {
    $redirectPath = isset($dashboard_view) ? 'index.php' : '../../index.php';
    header("Location: " . $redirectPath . "?login=1&error=" . urlencode("Driver information not found."));
    exit;
}

$basePath = isset($dashboard_view) ? '' : '../../';
$imageBasePath = $basePath;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - E-JEEP</title>
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/index.css" rel="stylesheet" type="text/css">
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/dashboard.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* ── Profile Zoom Modal ── */
        .profile-zoom-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.82);
            align-items: center;
            justify-content: center;
            animation: profileFadeIn .2s ease;
        }
        .profile-zoom-modal.open {
            display: flex;
        }
        @keyframes profileFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        .profile-zoom-inner {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }
        .profile-zoom-img {
            width: min(320px, 80vw);
            height: min(320px, 80vw);
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 8px 32px rgba(0,0,0,.5);
            animation: profileZoomIn .25s cubic-bezier(.34,1.56,.64,1);
        }
        @keyframes profileZoomIn {
            from { transform: scale(.6); opacity: 0; }
            to   { transform: scale(1);  opacity: 1; }
        }
        .profile-zoom-placeholder {
            width: min(280px, 72vw);
            height: min(280px, 72vw);
            border-radius: 50%;
            background: #16a34a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(72px, 18vw, 120px);
            font-weight: 700;
            color: #fff;
            letter-spacing: 4px;
            border: 4px solid #fff;
            box-shadow: 0 8px 32px rgba(0,0,0,.5);
            animation: profileZoomIn .25s cubic-bezier(.34,1.56,.64,1);
        }
        .profile-zoom-close {
            position: absolute;
            top: -48px;
            right: -8px;
            background: none;
            border: none;
            color: #fff;
            font-size: 36px;
            line-height: 1;
            cursor: pointer;
            opacity: .85;
            transition: opacity .15s;
        }
        .profile-zoom-close:hover { opacity: 1; }
        .profile-zoom-name {
            color: #fff;
            font-size: 18px;
            font-weight: 600;
            letter-spacing: .5px;
            text-shadow: 0 2px 8px rgba(0,0,0,.4);
        }

        /* Make profile clickable */
        .dashboard-profile-image .profile-avatar,
        .dashboard-profile-image .profile-avatar-placeholder {
            cursor: pointer;
            transition: transform .2s, box-shadow .2s;
        }
        .dashboard-profile-image .profile-avatar:hover,
        .dashboard-profile-image .profile-avatar-placeholder:hover {
            transform: scale(1.08);
            box-shadow: 0 4px 16px rgba(0,0,0,.25);
        }
    </style>
</head>

<body>
    <!-- Dashboard Header at Top -->
    <div class="dashboard-header-top">
        <div class="dashboard-header-content">
            <div class="dashboard-header-text">
                <h1 class="dashboard-title">Driver Dashboard</h1>
                <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($driverInfo['first_name']); ?>!</p>
            </div>
            <div class="dashboard-profile-image">
                <?php
                    $fullName = htmlspecialchars($driverInfo['first_name'] . ' ' . $driverInfo['last_name']);
                    $initials = strtoupper(substr($driverInfo['first_name'], 0, 1) . substr($driverInfo['last_name'], 0, 1));
                ?>
                <?php if (!empty($driverInfo['profile_image']) && file_exists($imageBasePath . $driverInfo['profile_image'])): ?>
                    <img
                        src="<?php echo htmlspecialchars($imageBasePath . $driverInfo['profile_image']); ?>"
                        alt="Profile"
                        class="profile-avatar"
                        title="Click to view profile photo"
                        onclick="openProfileZoom('img', '<?php echo htmlspecialchars($imageBasePath . $driverInfo['profile_image']); ?>', '<?php echo $fullName; ?>')"
                    >
                <?php else: ?>
                    <div
                        class="profile-avatar-placeholder"
                        title="Click to view profile"
                        onclick="openProfileZoom('initials', '<?php echo $initials; ?>', '<?php echo $fullName; ?>')"
                    >
                        <?php echo $initials; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">

            <!-- Status Alert -->
            <?php if ($driverInfo['driver_status'] === 'pending'): ?>
                <div class="alert alert-warning">
                    <strong>Application Pending:</strong> Your driver application is under review. You will be notified once approved.
                </div>
            <?php elseif ($driverInfo['driver_status'] === 'rejected'): ?>
                <div class="alert alert-error">
                    <strong>Application Rejected:</strong> Your driver application has been rejected. Please contact support for more information.
                </div>
            <?php elseif ($driverInfo['driver_status'] === 'approved'): ?>
                <div class="alert alert-success">
                    <strong>Application Approved:</strong> Your driver account is active and ready to use!
                </div>
            <?php endif; ?>

            <!-- Dashboard Cards -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-bus"></i></div>
                    <h3 class="card-title">Driver Status</h3>
                    <p class="card-value"><?php echo ucfirst($driverInfo['driver_status'] ?? 'N/A'); ?></p>
                </div>

                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-id-card"></i></div>
                    <h3 class="card-title">E-JEEP Card</h3>
                    <p class="card-value"><?php echo $driverInfo['card_number'] ? htmlspecialchars($driverInfo['card_number']) : 'Not Issued'; ?></p>
                </div>

                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-id-badge"></i></div>
                    <h3 class="card-title">License Number</h3>
                    <p class="card-value"><?php echo htmlspecialchars($driverInfo['license_number'] ?? 'N/A'); ?></p>
                </div>

                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-mobile-alt"></i></div>
                    <h3 class="card-title">Device Assigned</h3>
                    <p class="card-value"><?php echo !empty($driverInfo['device_name']) ? htmlspecialchars($driverInfo['device_name']) : 'Not Assigned'; ?></p>
                    <?php if (!empty($driverInfo['device_serial_number'])): ?>
                        <p class="card-subvalue">SN: <?php echo htmlspecialchars($driverInfo['device_serial_number']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- License Image with Flip -->
            <div class="images-section">
                <div class="license-flip-card" onclick="flipLicense()">
                    <div class="flip-card-inner" id="flipCardInner">
                        <!-- Front Side -->
                        <div class="flip-card-front">
                            <div class="image-card">
                                <h3 class="image-card-title">Driver's License - Front</h3>
                                <div class="image-container">
                                    <?php if (!empty($driverInfo['license_image']) && file_exists($imageBasePath . $driverInfo['license_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($imageBasePath . $driverInfo['license_image']); ?>" alt="Driver's License Front" class="license-image" id="licenseImageFront">
                                        <div class="image-overlay">
                                            <button class="view-fullscreen-btn" onclick="event.stopPropagation(); viewFullscreen('licenseImageFront')">&#128269; View Fullscreen</button>
                                        </div>
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <span class="placeholder-icon"><i class="fas fa-file-alt"></i></span>
                                            <p class="placeholder-text">No Front License Image</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($driverInfo['license_number'])): ?>
                                    <p class="license-info">License #: <?php echo htmlspecialchars($driverInfo['license_number']); ?></p>
                                <?php endif; ?>
                                <p class="flip-hint">&#128070; Click to flip to back</p>
                            </div>
                        </div>

                        <!-- Back Side -->
                        <div class="flip-card-back">
                            <div class="image-card">
                                <h3 class="image-card-title">Driver's License - Back</h3>
                                <div class="image-container">
                                    <?php if (!empty($driverInfo['license_back_image']) && file_exists($imageBasePath . $driverInfo['license_back_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($imageBasePath . $driverInfo['license_back_image']); ?>" alt="Driver's License Back" class="license-image" id="licenseImageBack">
                                        <div class="image-overlay">
                                            <button class="view-fullscreen-btn" onclick="event.stopPropagation(); viewFullscreen('licenseImageBack')">&#128269; View Fullscreen</button>
                                        </div>
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <span class="placeholder-icon"><i class="fas fa-file-alt"></i></span>
                                            <p class="placeholder-text">No Back License Image</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($driverInfo['license_type'])): ?>
                                    <p class="license-info">Type: <?php echo htmlspecialchars($driverInfo['license_type']); ?></p>
                                <?php endif; ?>
                                <p class="flip-hint">&#128070; Click to flip to front</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Information -->
            <div class="dashboard-section">
                <h2 class="section-title">Profile Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($driverInfo['first_name'] . ' ' . $driverInfo['last_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($driverInfo['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($driverInfo['phone_number']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Address:</span>
                        <span class="info-value"><?php echo htmlspecialchars($driverInfo['address_line1'] . ', ' . $driverInfo['city'] . ', ' . $driverInfo['province']); ?></span>
                    </div>
                    <?php if (!empty($driverInfo['license_type'])): ?>
                        <div class="info-item">
                            <span class="info-label">License Type:</span>
                            <span class="info-value"><?php echo htmlspecialchars($driverInfo['license_type']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($driverInfo['license_expiry_date'])): ?>
                        <div class="info-item">
                            <span class="info-label">License Expiry:</span>
                            <span class="info-value"><?php echo date('F d, Y', strtotime($driverInfo['license_expiry_date'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Fullscreen Image Modal (existing — untouched) -->
    <div id="imageModal" class="image-modal" onclick="closeFullscreen()">
        <span class="modal-close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <!-- ── Profile Zoom Modal (NEW) ── -->
    <div id="profileZoomModal" class="profile-zoom-modal" role="dialog" aria-modal="true" aria-label="Profile photo">
        <div class="profile-zoom-inner">
            <button class="profile-zoom-close" onclick="closeProfileZoom()" aria-label="Close">&times;</button>
            <!-- filled dynamically by JS -->
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <?php
    $activePage = 'dashboard';
    include __DIR__ . '/components/bottom_navbar.php';
    ?>

    <script src="<?php echo htmlspecialchars($basePath); ?>assets/script/driver/dashboard.js"></script>

    <script>
      // ── Profile Zoom Feature ──────────────────────────────────────────────
      function openProfileZoom(type, value, name) {
        var modal  = document.getElementById('profileZoomModal');
        var inner  = modal.querySelector('.profile-zoom-inner');

        // Clear previous content (keep close button)
        var closeBtn = inner.querySelector('.profile-zoom-close');
        inner.innerHTML = '';
        inner.appendChild(closeBtn);

        if (type === 'img') {
          var img = document.createElement('img');
          img.src = value;
          img.alt = name || 'Profile Photo';
          img.className = 'profile-zoom-img';
          inner.appendChild(img);
        } else {
          // Initials placeholder
          var ph = document.createElement('div');
          ph.className = 'profile-zoom-placeholder';
          ph.textContent = value;
          inner.appendChild(ph);
        }

        if (name) {
          var nameEl = document.createElement('div');
          nameEl.className = 'profile-zoom-name';
          nameEl.textContent = name;
          inner.appendChild(nameEl);
        }

        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
      }

      function closeProfileZoom() {
        var modal = document.getElementById('profileZoomModal');
        modal.classList.remove('open');
        document.body.style.overflow = '';
      }

      document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('profileZoomModal');
        if (!modal) return;

        // Click on backdrop closes modal
        modal.addEventListener('click', function (e) {
          if (e.target === modal) closeProfileZoom();
        });

        // ESC key closes modal
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && modal.classList.contains('open')) closeProfileZoom();
        });
      });
      // ─────────────────────────────────────────────────────────────────────
    </script>
</body>

</html>