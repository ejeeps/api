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
      /* Map container sizing */
      #busTrackerMap { height: 60vh; min-height: 320px; border-radius: 12px; overflow: hidden; box-shadow: 0 8px 18px rgba(0,0,0,0.12); }
      /* Live Tracker Modal */
      .tracker-modal { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; z-index: 1100; }
      .tracker-modal.open { display: block; }
      .tracker-modal .modal-content { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); width: min(960px, 92vw); height: min(78vh, 680px); background: #ffffff; border-radius: 12px; box-shadow: 0 16px 40px rgba(0,0,0,0.25); display: flex; flex-direction: column; overflow: hidden; }
      .tracker-modal .modal-header { padding: 12px 16px; border-bottom: 1px solid rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; }
      .tracker-modal .modal-title { font-size: 1rem; font-weight: 600; }
      .tracker-modal .modal-close { background: transparent; border: none; font-size: 1.25rem; cursor: pointer; padding: 6px; line-height: 1; }
      .tracker-modal .modal-body { flex: 1; }
      .tracker-modal .modal-body #busTrackerMap { height: 100%; min-height: 0; border-radius: 0; box-shadow: none; }
      /* Floating live tracking button */
      .floating-track-btn {
        position: fixed;
        right: 16px;
        bottom: 96px; /* keep clear of bottom navbar */
        z-index: 1000;
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: #22c55e;
        color: #ffffff;
        border: none;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.05s ease, background 0.2s ease, box-shadow 0.2s ease;
      }
      .floating-track-btn:hover { background: #16a34a; box-shadow: 0 10px 18px rgba(0,0,0,0.24); }
      .floating-track-btn:active { transform: translateY(1px); }
      .floating-track-btn i { font-size: 1.2rem; }
      @media (min-width: 1024px) { .floating-track-btn { right: 24px; bottom: 120px; } }
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
                    <p class="card-value">₱<?php echo number_format($passengerInfo['card_balance'] ?? 0.00, 2); ?></p>
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
            <style>
            .ejeep-card-wrap { margin-top: 16px; }
            .ejeep-card {
                position: relative;
                width: 100%;
                max-width: 420px;
                aspect-ratio: 16/10;
                
                padding: 20px;
                color: #fff;
                background: radial-gradient(120% 120% at 0% 0%, #22c55e 0%, #16a34a 45%, #065f46 100%);
                box-shadow: 0 12px 24px rgba(0,0,0,0.25);
                overflow: hidden;
            }
            .ejeep-card .card-brand { position:absolute; top:16px; right:16px; font-weight:700; letter-spacing:1px; }
            .ejeep-card .chip { width:44px; height:32px; border-radius:6px; background:linear-gradient(135deg,#f8f8f8,#cfcfcf); box-shadow:inset 0 1px 2px rgba(0,0,0,0.2); }
            .ejeep-card .row { display:flex; align-items:center; justify-content:space-between; }
            .ejeep-card .number { margin-top:14px; font-size: clamp(1rem, 4vw, 1.2rem); letter-spacing:1.2px; white-space: nowrap; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
            .ejeep-card .holder { margin-top:18px; font-size:0.9rem; opacity:0.9; }
            .ejeep-card .meta { margin-top:6px; font-size:0.8rem; opacity:0.85; display:flex; gap:16px; }
            .ejeep-card .glow { position:absolute; inset:-40%; background: radial-gradient(50% 50% at 50% 50%, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0) 60%); filter: blur(30px); pointer-events:none; }
            .ejeep-card .badge { position:absolute; bottom:16px; right:16px; background: rgba(34,197,94,0.18); padding:6px 10px; border-radius:999px; font-size:0.75rem; backdrop-filter: blur(4px); }
            .ejeep-card .logo { display:flex; align-items:center; gap:8px; font-weight:700; }
            .ejeep-card .logo .dot { width:10px; height:10px; border-radius:50%; background:#22c55e; box-shadow:0 0 8px rgba(34,197,94,0.9); }
            @media (min-width: 640px){ .ejeep-card { aspect-ratio: 16/9; } }
            </style>
            <div class="dashboard-section ejeep-card-wrap">
               
                <div class="ejeep-card" aria-label="Your virtual E-JEEP card">
                    <div class="glow"></div>
                    <div class="row">
                        <div class="logo"><span class="dot"></span><span>E‑JEEP</span></div>
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
                        <span>BAL: ₱<?php echo number_format($passengerInfo['card_balance'] ?? 0.00, 2); ?></span>
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
                                            <button class="view-fullscreen-btn" onclick="event.stopPropagation(); viewFullscreen('idImageFront')">🔍 View Fullscreen</button>
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
                                <p class="flip-hint">👆 Click to flip to back</p>
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
                                            <button class="view-fullscreen-btn" onclick="event.stopPropagation(); viewFullscreen('idImageBack')">🔍 View Fullscreen</button>
                                        </div>
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <span class="placeholder-icon"><i class="fas fa-file-alt"></i></span>
                                            <p class="placeholder-text">No Back ID Image</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <p class="flip-hint">👆 Click to flip to front</p>
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

    <!-- Fullscreen Image Modal -->
    <div id="imageModal" class="image-modal" onclick="closeFullscreen()">
        <span class="modal-close">&times;</span>
        <img class="modal-content" id="modalImage">
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
      // Floating button opens modal; modal close via overlay/close button; triggers Leaflet resize without re-render
      (function(){
        document.addEventListener('DOMContentLoaded', function(){
          var btn = document.getElementById('floatingTrackBtn');
          var modal = document.getElementById('trackerModal');
          var closeBtn = modal ? modal.querySelector('.modal-close') : null;
          if (!btn || !modal) return;

          function openModal(){
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            // Allow CSS to render, then trigger resize so Leaflet invalidates size
            setTimeout(function(){ window.dispatchEvent(new Event('resize')); }, 120);
          }
          function closeModal(){
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
          }

          btn.addEventListener('click', openModal);
          if (closeBtn) closeBtn.addEventListener('click', closeModal);
          // Click outside content to close
          modal.addEventListener('click', function(ev){ if (ev.target === modal) closeModal(); });
          // ESC key to close
          document.addEventListener('keydown', function(ev){ if (ev.key === 'Escape' && modal.classList.contains('open')) closeModal(); });
        });
      })();
    </script>
</body>
</html>

