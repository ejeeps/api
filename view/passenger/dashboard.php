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
    <title>Passenger Dashboard - E-JEEP</title>
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/index.css" rel="stylesheet" type="text/css">
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/dashboard.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    <style>
        

        /* Backdrop overlay */
        .logout-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 99999;
            align-items: center;
            justify-content: center;
        }

        .logout-modal-overlay.active {
            display: flex;
        }

        /* Modal card */
        .logout-modal-box {
            background: #ffffff;
            border-radius: 20px;
            padding: 40px 36px 32px;
            width: 92%;
            max-width: 420px;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.2);
            text-align: center;
            position: relative;
            animation: logoutModalPop 0.28s cubic-bezier(0.34, 1.56, 0.64, 1) both;
        }

        @keyframes logoutModalPop {
            from { opacity: 0; transform: scale(0.88) translateY(20px); }
            to   { opacity: 1; transform: scale(1)   translateY(0);     }
        }

        /* ✕ Close button */
        .logout-modal-close-x {
            position: absolute;
            top: 14px;
            right: 16px;
            background: #f4f7f5;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #718096;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, color 0.2s;
        }

        .logout-modal-close-x:hover {
            background: #e2e8f0;
            color: #1a202c;
        }

        /* Red icon circle */
        .logout-modal-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #fff3f3;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
            color: #e53e3e;
        }

        .logout-modal-box h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 10px;
        }

        .logout-modal-box p {
            font-size: 0.93rem;
            color: #718096;
            line-height: 1.65;
            margin: 0 0 28px;
        }

        /* Buttons row */
        .logout-modal-actions {
            display: flex;
            gap: 12px;
        }

        .logout-modal-actions button {
            flex: 1;
            padding: 13px 18px;
            border-radius: 12px;
            font-size: 0.93rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }

        .logout-modal-actions button:active {
            transform: scale(0.97);
        }

        /* Cancel — grey */
        .btn-logout-cancel {
            background: #f4f7f5;
            color: #1a202c;
            border: 1.5px solid #e2e8f0 !important;
        }

        .btn-logout-cancel:hover {
            background: #e2e8f0;
        }

        /* Confirm — red */
        .btn-logout-confirm {
            background: linear-gradient(135deg, #e53e3e, #c53030);
            color: #ffffff;
            box-shadow: 0 4px 16px rgba(229, 62, 62, 0.30);
        }

        .btn-logout-confirm:hover {
            box-shadow: 0 6px 20px rgba(229, 62, 62, 0.45);
            filter: brightness(1.06);
        }

        .btn-logout-confirm:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            filter: none;
        }
    </style>
</head>

<body>
    <!-- Dashboard Header at Top -->
    <div class="dashboard-header-top">
        <div class="dashboard-header-content">
            <div class="dashboard-header-text">
                <h1 class="dashboard-title">Passenger Dashboard</h1>
                <p class="dashboard-subtitle">Welcome back, <?php echo htmlspecialchars($passengerInfo['first_name']); ?>!</p>
            </div>
            <div class="dashboard-profile-image">
                <?php
                    $fullName = htmlspecialchars($passengerInfo['first_name'] . ' ' . $passengerInfo['last_name']);
                    $initials = strtoupper(substr($passengerInfo['first_name'], 0, 1) . substr($passengerInfo['last_name'], 0, 1));
                ?>
                <?php if (!empty($passengerInfo['profile_image']) && file_exists($imageBasePath . $passengerInfo['profile_image'])): ?>
                    <img
                        src="<?php echo htmlspecialchars($imageBasePath . $passengerInfo['profile_image']); ?>"
                        alt="Profile"
                        class="profile-avatar"
                        title="Click to view profile photo"
                        onclick="openProfileZoom('img', '<?php echo htmlspecialchars($imageBasePath . $passengerInfo['profile_image']); ?>', '<?php echo $fullName; ?>')"
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
            <?php if ($passengerInfo['card_status'] === 'not_issued'): ?>
                <div class="alert alert-warning">
                    <strong>Card Not Issued:</strong> Your E-JEEP card has not been issued yet. Please contact support.
                </div>
            <?php elseif ($passengerInfo['card_status'] === 'blocked'): ?>
                <div class="alert alert-error">
                    <strong>Card Blocked:</strong> Your E-JEEP card has been blocked. Please contact support for assistance.
                </div>
            <?php elseif ($passengerInfo['card_status'] === 'inactive'): ?>
                <div class="alert alert-warning">
                    <strong>Card Inactive:</strong> Your E-JEEP card is currently inactive. Please contact support to activate it.
                </div>
            <?php elseif ($passengerInfo['card_status'] === 'active'): ?>
                <div class="alert alert-success">
                    <strong>Card Active:</strong> Your E-JEEP card is active and ready to use!
                </div>
            <?php endif; ?>

            <!-- Dashboard Cards -->
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-id-card"></i></div>
                    <h3 class="card-title">E-JEEP Card</h3>
                    <p class="card-value"><?php echo $passengerInfo['card_number'] ? htmlspecialchars($passengerInfo['card_number']) : 'Not Issued'; ?></p>
                </div>

                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-wallet"></i></div>
                    <h3 class="card-title">Card Balance</h3>
                    <p class="card-value">&#8369;<?php echo number_format($passengerInfo['card_balance'] ?? 0.00, 2); ?></p>
                </div>

                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-tag"></i></div>
                    <h3 class="card-title">Card Type</h3>
                    <p class="card-value"><?php echo ucfirst($passengerInfo['card_type'] ?? 'N/A'); ?></p>
                </div>

                <div class="dashboard-card">
                    <div class="card-icon"><i class="fas fa-user"></i></div>
                    <h3 class="card-title">Account Status</h3>
                    <p class="card-value"><?php echo ucfirst(str_replace('_', ' ', $passengerInfo['status'] ?? 'N/A')); ?></p>
                </div>
            </div>

            <?php if (!empty($passengerInfo['card_number']) && ($passengerInfo['card_status'] ?? '') === 'active'): ?>
            <!-- E-JEEP Virtual Card -->
            <div class="dashboard-section ejeep-card-wrap">
               
                <div class="ejeep-card" aria-label="Your virtual E-JEEP card">
                    <div class="glow"></div>
                    <div class="row">
                        <div class="logo"><span class="dot"></span><span>E&#8209;JEEP</span></div>
                        <div class="card-brand">VIRTUAL</div>
                    </div>
                    <div class="chip" title="Secure chip"></div>
                    <div class="number">
                        <?php
                            $raw = preg_replace('/\D+/', '', (string)$passengerInfo['card_number']);
                            $formatted = trim(chunk_split($raw, 4, ' '));
                            echo htmlspecialchars($formatted);
                        ?>
                    </div>
                    <div class="holder">CARDHOLDER: <?php echo htmlspecialchars(strtoupper(($passengerInfo['first_name'] ?? '') . ' ' . ($passengerInfo['last_name'] ?? ''))); ?></div>
                    <div class="meta">
                        <span>TYPE: <?php echo htmlspecialchars(strtoupper($passengerInfo['card_type'] ?? 'STANDARD')); ?></span>
                        <span>BAL: &#8369;<?php echo number_format($passengerInfo['card_balance'] ?? 0.00, 2); ?></span>
                    </div>
                    <div class="badge">ACTIVE</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ID Card Image with Flip -->
            <div class="images-section">
                <div class="license-flip-card" onclick="flipIdCard()">
                    <div class="flip-card-inner" id="flipCardInner">
                        <!-- Front Side -->
                        <div class="flip-card-front">
                            <div class="image-card">
                                <h3 class="image-card-title">ID Card - Front</h3>
                                <div class="image-container">
                                    <?php if (!empty($passengerInfo['id_image_front']) && file_exists($imageBasePath . $passengerInfo['id_image_front'])): ?>
                                        <img src="<?php echo htmlspecialchars($imageBasePath . $passengerInfo['id_image_front']); ?>" alt="ID Card Front" class="license-image" id="idImageFront">
                                        <div class="image-overlay">
                                            <button class="view-fullscreen-btn" onclick="event.stopPropagation(); viewFullscreen('idImageFront')">&#128269; View Fullscreen</button>
                                        </div>
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <span class="placeholder-icon"><i class="fas fa-file-alt"></i></span>
                                            <p class="placeholder-text">No Front ID Image</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($passengerInfo['id_number'])): ?>
                                    <p class="license-info">ID #: <?php echo htmlspecialchars($passengerInfo['id_number']); ?></p>
                                <?php endif; ?>
                                <p class="flip-hint">&#128070; Click to flip to back</p>
                            </div>
                        </div>

                        <!-- Back Side -->
                        <div class="flip-card-back">
                            <div class="image-card">
                                <h3 class="image-card-title">ID Card - Back</h3>
                                <div class="image-container">
                                    <?php if (!empty($passengerInfo['id_image_back']) && file_exists($imageBasePath . $passengerInfo['id_image_back'])): ?>
                                        <img src="<?php echo htmlspecialchars($imageBasePath . $passengerInfo['id_image_back']); ?>" alt="ID Card Back" class="license-image" id="idImageBack">
                                        <div class="image-overlay">
                                            <button class="view-fullscreen-btn" onclick="event.stopPropagation(); viewFullscreen('idImageBack')">&#128269; View Fullscreen</button>
                                        </div>
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <span class="placeholder-icon"><i class="fas fa-file-alt"></i></span>
                                            <p class="placeholder-text">No Back ID Image</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <p class="flip-hint">&#128070; Click to flip to front</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <?php
    $activePage = 'dashboard';
    include __DIR__ . '/components/bottom_navbar.php';
    ?>

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

    <!-- Live Bus Tracker Modal -->
    <div id="trackerModal" class="tracker-modal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Live Bus Tracker</div>
                <button type="button" class="modal-close" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="busTrackerMap"></div>
            </div>
        </div>
    </div>

    <!-- Floating Live Tracking Button -->
    <button id="floatingTrackBtn" class="floating-track-btn" aria-label="Live Bus Tracker">
        <i class="fas fa-location-arrow"></i>
    </button>

    <!-- ══════════════════════════════════════════
         LOGOUT CONFIRMATION MODAL
         — placed just before </body> so it renders
           on top of all other page content
    ══════════════════════════════════════════ -->
    <div class="logout-modal-overlay"
         id="logoutModalOverlay"
         role="dialog"
         aria-modal="true"
         aria-labelledby="logoutModalTitle">

        <div class="logout-modal-box">

            <!-- ✕ top-right close button -->
            <button class="logout-modal-close-x" id="logoutModalCloseX" aria-label="Close dialog">
                <i class="fas fa-times"></i>
            </button>

            <!-- Warning icon -->
            <div class="logout-modal-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>

            <!-- Heading & description -->
            <h3 id="logoutModalTitle">Logout Confirmation</h3>
            <p>
                Are you sure you want to logout of your<br>
                <strong>E-JEEP account</strong>?<br>
                <span style="font-size:.82rem;">
                    You will need to login again to access your dashboard.
                </span>
            </p>

            <!-- Action buttons -->
            <div class="logout-modal-actions">
                <button class="btn-logout-cancel"  id="logoutCancelBtn"  type="button">
                    <i class="fas fa-times"          style="margin-right:6px;"></i>Cancel
                </button>
                <button class="btn-logout-confirm" id="logoutConfirmBtn" type="button">
                    <i class="fas fa-sign-out-alt"  style="margin-right:6px;"></i>Yes, Logout
                </button>
            </div>

        </div>
    </div>
    <!-- END LOGOUT MODAL -->

    <!-- Leaflet JS and live tracker -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="<?php echo htmlspecialchars($basePath); ?>assets/script/passenger/live-tracker.js"></script>
    <script src="<?php echo htmlspecialchars($basePath); ?>assets/script/passenger/dashboard.js"></script>

    <script>
      // ── Live Bus Tracker modal ──────────────────────────────────────────────
      (function () {
        document.addEventListener('DOMContentLoaded', function () {
          var btn      = document.getElementById('floatingTrackBtn');
          var modal    = document.getElementById('trackerModal');
          var closeBtn = modal ? modal.querySelector('.modal-close') : null;
          if (!btn || !modal) return;

          function openTracker() {
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 120);
          }
          function closeTracker() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
          }

          btn.addEventListener('click', openTracker);
          if (closeBtn) closeBtn.addEventListener('click', closeTracker);
          modal.addEventListener('click', function (ev) { if (ev.target === modal) closeTracker(); });
          document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && modal.classList.contains('open')) closeTracker();
          });
        });
      })();

      // ── Logout Confirmation Modal ───────────────────────────────────────────
      (function () {
        'use strict';

        var pendingLogoutUrl = '';   // set when a trigger is clicked

        var overlay    = document.getElementById('logoutModalOverlay');
        var cancelBtn  = document.getElementById('logoutCancelBtn');
        var confirmBtn = document.getElementById('logoutConfirmBtn');
        var closeXBtn  = document.getElementById('logoutModalCloseX');

        /** Show the modal */
        function openLogoutModal(url) {
          pendingLogoutUrl = url;
          overlay.classList.add('active');
          document.body.style.overflow = 'hidden'; // prevent background scroll
          cancelBtn.focus();                        // a11y: focus first button
        }

        /** Hide the modal */
        function closeLogoutModal() {
          overlay.classList.remove('active');
          document.body.style.overflow = '';
        }

        /** Redirect to logout endpoint */
        function doLogout() {
          confirmBtn.disabled = true;
          confirmBtn.innerHTML =
            '<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i>Logging out…';
          window.location.href = pendingLogoutUrl;
        }

        // Attach to all [data-logout-trigger] elements on the page.
        // Reads data-logout-url from each trigger so the correct
        // LogoutController.php path is used regardless of $basePath.
        document.addEventListener('DOMContentLoaded', function () {
          document.querySelectorAll('[data-logout-trigger]').forEach(function (el) {
            el.addEventListener('click', function (e) {
              e.preventDefault();
              var url = el.getAttribute('data-logout-url') ||
                        '<?php echo htmlspecialchars($basePath); ?>controller/auth/LogoutController.php';
              openLogoutModal(url);
            });
          });
        });

        // Button listeners
        cancelBtn.addEventListener('click',  closeLogoutModal);
        closeXBtn.addEventListener('click',  closeLogoutModal);
        confirmBtn.addEventListener('click', doLogout);

        // Click on the dim backdrop → close
        overlay.addEventListener('click', function (e) {
          if (e.target === overlay) closeLogoutModal();
        });

        // Escape key → close
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && overlay.classList.contains('active')) {
            closeLogoutModal();
          }
        });

      })();
    </script>
</body>
</html>