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
            border-radius: 50%;
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

    <!-- Leaflet JS and live tracker -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="<?php echo htmlspecialchars($basePath); ?>assets/script/passenger/live-tracker.js"></script>
    <script src="<?php echo htmlspecialchars($basePath); ?>assets/script/passenger/dashboard.js"></script>

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

        // Click on backdrop (not inner content) closes modal
        modal.addEventListener('click', function (e) {
          if (e.target === modal) closeProfileZoom();
        });

        // ESC key closes modal
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && modal.classList.contains('open')) closeProfileZoom();
        });
      });
      // ─────────────────────────────────────────────────────────────────────

      // Floating button opens Live Tracker modal (existing — untouched)
      (function(){
        document.addEventListener('DOMContentLoaded', function(){
          var btn = document.getElementById('floatingTrackBtn');
          var modal = document.getElementById('trackerModal');
          var closeBtn = modal ? modal.querySelector('.modal-close') : null;
          if (!btn || !modal) return;

          function openModal(){
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            setTimeout(function(){ window.dispatchEvent(new Event('resize')); }, 120);
          }
          function closeModal(){
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
          }

          btn.addEventListener('click', openModal);
          if (closeBtn) closeBtn.addEventListener('click', closeModal);
          modal.addEventListener('click', function(ev){ if (ev.target === modal) closeModal(); });
          document.addEventListener('keydown', function(ev){ if (ev.key === 'Escape' && modal.classList.contains('open')) closeModal(); });
        });
      })();
    </script>
</body>
</html>