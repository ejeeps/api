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
require_once __DIR__ . '/../../controller/passenger/get_dashboard_trips_today.php';
$passengerInfo = getPassengerInfo($pdo, $_SESSION['user_id']);
$tripsTodayRoutes = getDashboardTripsToday($pdo);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=no">
    <meta name="theme-color" content="#16a34a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="E-JEEP Passenger">
    <meta name="description" content="E-JEEP Passenger Dashboard">
    <link rel="manifest" href="<?php echo htmlspecialchars($basePath); ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-192x192.png">
    <title>Passenger Dashboard - E-JEEP</title>
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/index.css" rel="stylesheet" type="text/css">
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/dashboard.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="<?php echo htmlspecialchars($basePath); ?>assets/script/pwa.js"></script>

    <style>
        /* ── Profile Zoom Modal ── */
        .profile-zoom-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 99998;
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

        /* Make profile avatar clickable */
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

        /* ── Logout Modal ── */

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

        /* X Close button */
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
            <?php elseif ($passengerInfo['card_status'] === 'expired'): ?>
                <div class="alert alert-error">
                    <strong>Card Expired:</strong> Your E-JEEP card has expired. Please contact support to renew it.
                </div>
            <?php endif; ?>

            <div class="passenger-dashboard-flow">
            <!-- Dashboard Cards -->
            <div class="passenger-dashboard-flow__stats">
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
                    <div class="card-icon"><i class="fas fa-building"></i></div>
                    <h3 class="card-title">Organization</h3>
                    <p class="card-value"><?php echo $passengerInfo['organization_name'] ? htmlspecialchars($passengerInfo['organization_name']) : 'None'; ?></p>
                </div>
            </div>
            </div>

            <!-- Trips today: routes with activity (network overview) -->
            <div class="passenger-dashboard-flow__trips">
            <div class="dashboard-section trips-today-section">
                <h2 class="section-title trips-today-title">
                    <i class="fas fa-bus" aria-hidden="true"></i>
                    Trips today
                </h2>
                <p class="trips-today-subtitle">
                    <span class="trips-today-date"><?php echo htmlspecialchars(date('l, F j, Y')); ?></span><span class="trips-today-subtitle-extra"> · Routes with activity today</span>
                </p>
                <?php if (empty($tripsTodayRoutes)): ?>
                    <div class="trips-today-empty" role="status">
                        <i class="fas fa-road" aria-hidden="true"></i>
                        <p>No trip activity has been recorded on the network yet today.</p>
                        <p class="trips-today-empty-hint">When vehicles run and taps are logged, routes will appear here.</p>
                    </div>
                <?php else: ?>
                    <ul class="trips-today-list">
                        <?php foreach ($tripsTodayRoutes as $row):
                            $from = isset($row['from_location']) ? (string)$row['from_location'] : '';
                            $to = isset($row['to_location']) ? (string)$row['to_location'] : '';
                            $extra = isset($row['location']) ? trim((string)$row['location']) : '';
                            $sessions = (int)($row['trip_sessions'] ?? 0);
                            $pending = (int)($row['pending_rows'] ?? 0);
                            $lastTs = !empty($row['last_activity']) ? strtotime((string)$row['last_activity']) : false;
                            $lastLabel = $lastTs ? date('g:i A', $lastTs) : '';
                            $ongoing = $pending > 0;
                            ?>
                            <li class="trips-today-card">
                                <div class="trips-today-card-main">
                                    <div class="trips-today-route-line">
                                        <span class="trips-today-from"><?php echo htmlspecialchars($from); ?></span>
                                        <span class="trips-today-arrow" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                                        <span class="trips-today-to"><?php echo htmlspecialchars($to); ?></span>
                                    </div>
                                    <?php if ($extra !== ''): ?>
                                        <p class="trips-today-extra"><?php echo htmlspecialchars($extra); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="trips-today-card-meta">
                                    <?php if ($ongoing): ?>
                                        <span class="trips-today-badge trips-today-badge--ongoing"><span class="trips-today-pulse" aria-hidden="true"></span> Ongoing</span>
                                    <?php else: ?>
                                        <span class="trips-today-badge trips-today-badge--quiet">No open trip</span>
                                    <?php endif; ?>
                                    <span class="trips-today-stat"><?php echo $sessions; ?> trip<?php echo $sessions === 1 ? '' : 's'; ?> today</span>
                                    <?php if ($lastLabel !== ''): ?>
                                        <span class="trips-today-time">Last activity <?php echo htmlspecialchars($lastLabel); ?></span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            </div>

            <?php if (!empty($passengerInfo['card_number']) && ($passengerInfo['card_status'] ?? '') === 'active'): ?>
            <!-- E-JEEP Virtual Card -->
            <div class="passenger-dashboard-flow__card">
            <div class=" ejeep-card-wrap">
                <?php
                    $virtualCardRaw = preg_replace('/\D+/', '', (string)($passengerInfo['card_number'] ?? ''));
                    $virtualCardFormatted = trim(chunk_split($virtualCardRaw, 4, ' '));
                    $virtualCardMasked = str_repeat('*', max(0, strlen($virtualCardRaw) - 4)) . substr($virtualCardRaw, -4);
                    $virtualCardMaskedFormatted = trim(chunk_split($virtualCardMasked, 4, ' '));

                    $virtualBalanceFormatted = number_format((float)($passengerInfo['card_balance'] ?? 0.00), 2);
                    $virtualCardType = strtoupper((string)($passengerInfo['card_type'] ?? 'STANDARD'));
                    $virtualHolder = strtoupper(trim((string)($passengerInfo['first_name'] ?? '') . ' ' . (string)($passengerInfo['last_name'] ?? '')));
                    $virtualOrg = $passengerInfo['organization_name'] ? trim((string)$passengerInfo['organization_name']) : null;
                    $virtualCvvMasked = str_repeat('*', 3);
                    if (strlen($virtualCardRaw) >= 3) {
                        $virtualCvvMasked = substr($virtualCardRaw, -3);
                    }
                ?>

                <div
                    class="ejeep-flip-card"
                    data-balance-visible="true"
                    data-card-number-raw="<?php echo htmlspecialchars($virtualCardRaw); ?>"
                    role="button"
                    tabindex="0"
                    aria-label="Your virtual E-JEEP card. Click to flip."
                    onclick="flipVirtualEjeepCard(event)"
                    onkeydown="if(event.key === 'Enter' || event.key === ' ') { event.preventDefault(); flipVirtualEjeepCard(event); }"
                >
                    <div class="ejeep-flip-card-inner">
                        <!-- Front Side -->
                        <div class="ejeep-card ejeep-card-front" aria-hidden="false">
                            <div class="glow"></div>
                            <div class="row">
                                <div class="logo"><span class="dot"></span><span>E&#8209;JEEP</span></div>
                                <div class="card-brand">VIRTUAL</div>
                            </div>
                            <div class="chip" title="Secure chip"></div>

                            <div class="number-row">
                                <div class="number">
                                    <?php echo htmlspecialchars($virtualCardFormatted); ?>
                                </div>
                                <button
                                    type="button"
                                    class="ejeep-card-icon-btn copy-card-number-btn"
                                    aria-label="Copy card number"
                                    title="Copy card number"
                                    onclick="event.stopPropagation(); copyVirtualCardNumber(event);"
                                >
                                    <i class="fas fa-copy" aria-hidden="true"></i>
                                </button>
                            </div>

                            <div class="holder"><?php echo htmlspecialchars($virtualHolder); ?></div>

                            <div class="meta">
                                <span><?php echo htmlspecialchars($virtualCardType); ?></span>
                                <span class="meta-balance">
                                    <span class="meta-balance-text">
                                        BAL: &#8369;
                                        <span class="virtual-balance-visible"><?php echo htmlspecialchars($virtualBalanceFormatted); ?></span>
                                        <span class="virtual-balance-hidden">****.**</span>
                                    </span>
                                    <button
                                        type="button"
                                        class="ejeep-card-icon-btn balance-toggle-btn"
                                        aria-label="Hide balance"
                                        title="Hide balance"
                                        onclick="event.stopPropagation(); toggleVirtualBalanceVisibility(event);"
                                    >
                                        <i class="fas fa-eye virtual-eye-open" aria-hidden="true"></i>
                                        <i class="fas fa-eye-slash virtual-eye-off" aria-hidden="true"></i>
                                    </button>
                                </span>
                                <?php if ($virtualOrg): ?>
                                    <small class="meta-org">Organization: <?php echo htmlspecialchars($virtualOrg); ?></small>
                                <?php endif; ?>
                            </div>

                            <div class="badge">ACTIVE</div>
                        </div>

                        <!-- Back Side -->
                        <div class="ejeep-card ejeep-card-back" aria-hidden="true">
                            <div class="glow"></div>
                            <div class="row">
                                <div class="logo"><span class="dot"></span><span>E&#8209;JEEP</span></div>
                                <div class="card-brand">BACK</div>
                            </div>
                            <div class="card-back-strip" aria-hidden="true"></div>

                            <div class="card-back-signature-row">
                                <div class="card-back-signature">
                                    <?php echo htmlspecialchars($virtualHolder); ?>
                                </div>
                                <div class="card-back-cvv">
                                    CVV <?php echo htmlspecialchars($virtualCvvMasked); ?>
                                </div>
                            </div>

                            <div class="number back-number">
                                <?php echo htmlspecialchars($virtualCardMaskedFormatted); ?>
                            </div>

                            <div class="meta">
                                <span><?php echo htmlspecialchars($virtualCardType); ?></span>
                                <span class="meta-balance">
                                    <span class="meta-balance-text">
                                        BAL: &#8369;
                                        <span class="virtual-balance-visible"><?php echo htmlspecialchars($virtualBalanceFormatted); ?></span>
                                        <span class="virtual-balance-hidden">****.**</span>
                                    </span>
                                    <button
                                        type="button"
                                        class="ejeep-card-icon-btn balance-toggle-btn"
                                        aria-label="Hide balance"
                                        title="Hide balance"
                                        onclick="event.stopPropagation(); toggleVirtualBalanceVisibility(event);"
                                    >
                                        <i class="fas fa-eye virtual-eye-open" aria-hidden="true"></i>
                                        <i class="fas fa-eye-slash virtual-eye-off" aria-hidden="true"></i>
                                    </button>
                                </span>
                                <?php if ($virtualOrg): ?>
                                    <small class="meta-org">Organization: <?php echo htmlspecialchars($virtualOrg); ?></small>
                                <?php endif; ?>
                            </div>

                            <div class="badge">ACTIVE</div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            <?php endif; ?>

            </div><!-- .passenger-dashboard-flow -->

            
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

    <!-- ── Profile Zoom Modal ── -->
    <div id="profileZoomModal" class="profile-zoom-modal" role="dialog" aria-modal="true" aria-label="Profile photo">
        <div class="profile-zoom-inner">
            <button class="profile-zoom-close" onclick="closeProfileZoom()" aria-label="Close">&times;</button>
            <!-- Content filled dynamically by JS -->
        </div>
    </div>

       <?php include 'view/components/live_bus_tracker.php'; ?>

    <script src="<?php echo htmlspecialchars($basePath); ?>assets/script/passenger/dashboard.js"></script>

    <script>
      // ── Profile Zoom Feature ──────────────────────────────────────────────
      function openProfileZoom(type, value, name) {
        var modal    = document.getElementById('profileZoomModal');
        var inner    = modal.querySelector('.profile-zoom-inner');
        var closeBtn = inner.querySelector('.profile-zoom-close');

        // Clear previous dynamic content, keep the close button
        inner.innerHTML = '';
        inner.appendChild(closeBtn);

        if (type === 'img') {
          var img       = document.createElement('img');
          img.src       = value;
          img.alt       = name || 'Profile Photo';
          img.className = 'profile-zoom-img';
          inner.appendChild(img);
        } else {
          // Initials placeholder
          var ph           = document.createElement('div');
          ph.className     = 'profile-zoom-placeholder';
          ph.textContent   = value;
          inner.appendChild(ph);
        }

        if (name) {
          var nameEl           = document.createElement('div');
          nameEl.className     = 'profile-zoom-name';
          nameEl.textContent   = name;
          inner.appendChild(nameEl);
        }

        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
      }

      function closeProfileZoom() {
        document.getElementById('profileZoomModal').classList.remove('open');
        document.body.style.overflow = '';
      }

      document.addEventListener('DOMContentLoaded', function () {
        var profileModal = document.getElementById('profileZoomModal');
        if (profileModal) {
          // Click on dark backdrop closes modal
          profileModal.addEventListener('click', function (e) {
            if (e.target === profileModal) closeProfileZoom();
          });
        }
        // ESC closes profile zoom (logout ESC is handled in its own IIFE)
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') {
            var pModal = document.getElementById('profileZoomModal');
            if (pModal && pModal.classList.contains('open')) closeProfileZoom();
          }
        });
      });
      // ─────────────────────────────────────────────────────────────────────

      // ── Logout Confirmation Modal ─────────────────────────────────────────
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
          document.body.style.overflow = 'hidden';
          cancelBtn.focus();
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