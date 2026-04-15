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

$basePath      = isset($dashboard_view) ? '' : '../../';
$imageBasePath = $basePath;

// ── Fetch trip history grouped by trip_id ────────────────────────────────────
$tripHistory = [];
try {
    $stmtTrips = $pdo->prepare("
        SELECT
            t.trip_id,
            t.route_id,
            r.from_location,
            r.to_location,
            t.card_id,
            SUM(t.fare_amount)                                              AS total_fare,
            SUM(t.distance_km)                                              AS total_distance,
            MIN(t.timestamp)                                                AS trip_timestamp,
            MIN(t.timestamp)                                                AS tap_in_time,
            MAX(t.timestamp)                                                AS tap_out_time,
            GROUP_CONCAT(DISTINCT t.tap_level ORDER BY t.tap_level
                         SEPARATOR '/')                                     AS tap_levels,
            COUNT(*)                                                        AS tap_count,
            SUM(CASE WHEN UPPER(t.tap_level) = 'IN'  THEN 1 ELSE 0 END)   AS has_tap_in,
            SUM(CASE WHEN UPPER(t.tap_level) = 'OUT' THEN 1 ELSE 0 END)   AS has_tap_out,
            CASE
                WHEN SUM(CASE WHEN t.trip_status = 'assisted'  THEN 1 ELSE 0 END) > 0
                    THEN 'assisted'
                WHEN SUM(CASE WHEN t.trip_status = 'cancelled' THEN 1 ELSE 0 END) > 0
                    THEN 'cancelled'
                WHEN SUM(CASE WHEN UPPER(t.tap_level) = 'IN'  THEN 1 ELSE 0 END) > 0
                 AND SUM(CASE WHEN UPPER(t.tap_level) = 'OUT' THEN 1 ELSE 0 END) > 0
                    THEN 'completed'
                WHEN SUM(CASE WHEN UPPER(t.tap_level) = 'IN'  THEN 1 ELSE 0 END) > 0
                    THEN 'in-progress'
                WHEN SUM(CASE WHEN UPPER(t.tap_level) = 'OUT' THEN 1 ELSE 0 END) > 0
                    THEN 'completed'
                ELSE MAX(t.trip_status)
            END                                                             AS trip_status
        FROM trips t
        INNER JOIN device_assignments da ON t.driver_assign_id = da.id
        INNER JOIN drivers d             ON da.driver_id        = d.id
        LEFT  JOIN routes r              ON t.route_id          = r.id
        WHERE d.user_id = :user_id
        GROUP BY t.trip_id, t.route_id, t.card_id, r.from_location, r.to_location
        ORDER BY trip_timestamp DESC
    ");
    $stmtTrips->execute([':user_id' => $_SESSION['user_id']]);
    $tripHistory = $stmtTrips->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $tripHistory = [];
}

// ── Summary totals ────────────────────────────────────────────────────────────
$totalTrips    = count($tripHistory);
$totalEarnings = 0.0;
foreach ($tripHistory as $trip) {
    $totalEarnings += (float)($trip['total_fare'] ?? 0);
}
// ─────────────────────────────────────────────────────────────────────────────
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
    <meta name="apple-mobile-web-app-title" content="E-JEEP Driver">
    <meta name="description" content="E-JEEP Driver Dashboard">
    <link rel="manifest" href="<?php echo htmlspecialchars($basePath); ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-192x192.png">
    <title>Driver Dashboard - E-JEEP</title>
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/index.css" rel="stylesheet" type="text/css">
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/dashboard.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css " integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="<?php echo htmlspecialchars($basePath); ?>assets/script/pwa.js"></script>

    <style>
        /* ── Profile Zoom Modal ── */
        .profile-zoom-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0,0,0,.82);
            align-items: center;
            justify-content: center;
            animation: profileFadeIn .2s ease;
        }
        .profile-zoom-modal.open { display: flex; }
        @keyframes profileFadeIn { from{opacity:0} to{opacity:1} }
        .profile-zoom-inner {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }
        .profile-zoom-img {
            width: min(320px,80vw);
            height: min(320px,80vw);
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 8px 32px rgba(0,0,0,.5);
            animation: profileZoomIn .25s cubic-bezier(.34,1.56,.64,1);
        }
        @keyframes profileZoomIn {
            from{transform:scale(.6);opacity:0}
            to{transform:scale(1);opacity:1}
        }
        .profile-zoom-placeholder {
            width: min(280px,72vw);
            height: min(280px,72vw);
            border-radius: 50%;
            background: #16a34a;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(72px,18vw,120px);
            font-weight: 700;
            color: #fff;
            letter-spacing: 4px;
            border: 4px solid #fff;
            box-shadow: 0 8px 32px rgba(0,0,0,.5);
            animation: profileZoomIn .25s cubic-bezier(.34,1.56,.64,1);
        }
        .profile-zoom-close {
            position: absolute;
            top: -48px; right: -8px;
            background: none; border: none;
            color: #fff; font-size: 36px;
            line-height: 1; cursor: pointer;
            opacity: .85; transition: opacity .15s;
        }
        .profile-zoom-close:hover { opacity: 1; }
        .profile-zoom-name {
            color: #fff; font-size: 18px;
            font-weight: 600; letter-spacing: .5px;
            text-shadow: 0 2px 8px rgba(0,0,0,.4);
        }
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

        /* ── Trip History ── */
        .trip-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .trip-summary-card {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 0px;
            padding: 14px 12px;
            text-align: center;
            cursor: pointer;
            transition: transform .2s, box-shadow .2s;
        }
        .trip-summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.15);
        }
        .trip-summary-card .ts-icon  { font-size: 22px; margin-bottom: 6px; color: #16a34a; }
        .trip-summary-card .ts-value { font-size: 20px; font-weight: 700; color: #15803d; line-height: 1.1; }
        .trip-summary-card .ts-label { font-size: 11px; color: #6b7280; margin-top: 3px; text-transform: uppercase; letter-spacing: .5px; }

        .trip-controls {
            display: flex; flex-wrap: wrap; gap: 10px;
            margin-bottom: 16px; align-items: center;
        }
        .trip-search-input {
            flex: 1; min-width: 180px;
            padding: 8px 12px;
            border: 1px solid #d1d5db; border-radius: 0px;
            font-size: 14px; outline: none; transition: border-color .2s;
        }
        .trip-search-input:focus { border-color: #16a34a; }
        .trip-filter-select {
            padding: 8px 12px;
            border: 1px solid #d1d5db; border-radius: 0px;
            font-size: 14px; background: #fff;
            outline: none; cursor: pointer; transition: border-color .2s;
        }
        .trip-filter-select:focus { border-color: #16a34a; }

        .trip-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 0px;
            border: 1px solid #e5e7eb;
            max-height: 400px;
            overflow-y: auto;
        }
        .trip-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 500px; }
        .trip-table thead th {
            background: #16a34a; color: #fff;
            padding: 11px 14px; text-align: left;
            font-weight: 600; white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .trip-table thead th:first-child { border-radius: 0px 0 0 0; }
        .trip-table thead th:last-child  { border-radius: 0 0px 0 0; }
        .trip-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background .15s;
        }
        .trip-table tbody tr:last-child { border-bottom: none; }
        .trip-table tbody tr:hover { background: #f0fdf4; }
        .trip-table td {
            padding: 10px 14px; color: #374151;
            vertical-align: middle; white-space: nowrap;
        }

        /* Route arrow display */
        .route-cell {
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .route-cell .route-arrow { color: #16a34a; font-size: 12px; }
        .route-cell .route-loc   { font-weight: 500; color: #1f2937; }

        /* Empty state */
        .trip-empty { text-align: center; padding: 40px 20px; color: #9ca3af; }
        .trip-empty i { font-size: 40px; margin-bottom: 10px; display: block; }
        .trip-empty p { font-size: 14px; }

        /* Scroll loading indicator */
        .trip-scroll-loading {
            text-align: center;
            padding: 16px;
            color: #6b7280;
            font-size: 13px;
            display: none;
        }
        .trip-scroll-loading.show { display: block; }
        .trip-scroll-loading i {
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* ── Trip History Modal ── */
        .trip-history-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 10000;
            background: rgba(0,0,0,.75);
            align-items: center;
            justify-content: center;
            padding: 20px;
            animation: modalFadeIn .2s ease;
        }
        .trip-history-modal.open { display: flex; }
        @keyframes modalFadeIn { from{opacity:0} to{opacity:1} }
        .trip-history-modal-content {
            background: #fff;
            border-radius: 0px;
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: modalSlideIn .25s ease;
        }
        @keyframes modalSlideIn {
            from{transform:translateY(30px);opacity:0}
            to{transform:translateY(0);opacity:1}
        }
        .trip-history-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        .trip-history-modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .trip-history-modal-header h3 i {
            color: #16a34a;
        }
        .trip-history-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0px;
            transition: all .15s;
        }
        .trip-history-modal-close:hover {
            background: #f3f4f6;
            color: #374151;
        }
        .trip-history-modal-body {
            padding: 20px 24px;
            overflow-y: auto;
            flex: 1;
        }
        @media (max-width: 640px) {
            .trip-history-modal {
                padding: 0;
            }
            .trip-history-modal-content {
                max-height: 100vh;
                border-radius: 0;
            }
            .trip-history-modal-header {
                padding: 16px 20px;
            }
            .trip-history-modal-body {
                padding: 16px 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Dashboard Header -->
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
                        onclick="openProfileZoom('img','<?php echo htmlspecialchars($imageBasePath . $driverInfo['profile_image']); ?>','<?php echo $fullName; ?>')"
                    >
                <?php else: ?>
                    <div
                        class="profile-avatar-placeholder"
                        title="Click to view profile"
                        onclick="openProfileZoom('initials','<?php echo $initials; ?>','<?php echo $fullName; ?>')"
                    ><?php echo $initials; ?></div>
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
                    <p class="card-value"><?php $c = $driverInfo['card_number'] ?? ''; echo $c !== '' ? htmlspecialchars($c) : 'Not Issued'; ?></p>
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

            <!-- ── Trip Routes History Summary (Clickable) ─────────────────── -->
            <div class="dashboard-section">
                <h2 class="section-title">
                    <i class="fas fa-route" style="margin-right:8px;color:#16a34a;"></i>Trip Routes History
                </h2>

                <!-- Summary Cards -->
                <div class="trip-summary-grid">
                    <div class="trip-summary-card" onclick="openTripHistoryModal()">
                        <div class="ts-icon"><i class="fas fa-list-ol"></i></div>
                        <div class="ts-value"><?php echo $totalTrips; ?></div>
                        <div class="ts-label">Total Trips</div>
                    </div>
                    <div class="trip-summary-card">
                        <div class="ts-icon"><i class="fas fa-peso-sign"></i></div>
                        <div class="ts-value">&#8369;<?php echo number_format($totalEarnings, 2); ?></div>
                        <div class="ts-label">Total Earnings</div>
                    </div>
                </div>
            </div>
            <!-- ── End Trip Routes History Summary ─────────────────────────── -->

            <!-- License Image with Flip -->
            <div class="images-section">
                <div class="license-flip-card" onclick="flipLicense()">
                    <div class="flip-card-inner" id="flipCardInner">
                        <!-- Front -->
                        <div class="flip-card-front">
                            <div class="image-card">
                                <h3 class="image-card-title">Driver's License - Front</h3>
                                <div class="image-container">
                                    <?php if (!empty($driverInfo['license_image']) && file_exists($imageBasePath . $driverInfo['license_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($imageBasePath . $driverInfo['license_image']); ?>" alt="Driver's License Front" class="license-image" id="licenseImageFront">
                                        <div class="image-overlay">
                                            <button class="view-fullscreen-btn" onclick="event.stopPropagation();viewFullscreen('licenseImageFront')">&#128269; View Fullscreen</button>
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
                        <!-- Back -->
                        <div class="flip-card-back">
                            <div class="image-card">
                                <h3 class="image-card-title">Driver's License - Back</h3>
                                <div class="image-container">
                                    <?php if (!empty($driverInfo['license_back_image']) && file_exists($imageBasePath . $driverInfo['license_back_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($imageBasePath . $driverInfo['license_back_image']); ?>" alt="Driver's License Back" class="license-image" id="licenseImageBack">
                                        <div class="image-overlay">
                                            <button class="view-fullscreen-btn" onclick="event.stopPropagation();viewFullscreen('licenseImageBack')">&#128269; View Fullscreen</button>
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

        </div>
    </div>

    <!-- ── Trip History Modal ─────────────────────────────────────────────── -->
    <div id="tripHistoryModal" class="trip-history-modal" role="dialog" aria-modal="true" aria-label="Trip History">
        <div class="trip-history-modal-content">
            <div class="trip-history-modal-header">
                <h3><i class="fas fa-route"></i> Trip Routes History</h3>
                <button class="trip-history-modal-close" onclick="closeTripHistoryModal()" aria-label="Close">&times;</button>
            </div>
            <div class="trip-history-modal-body">
                <?php if (empty($tripHistory)): ?>
                    <div class="trip-empty">
                        <i class="fas fa-route"></i>
                        <p>No trip records found for your account.</p>
                    </div>
                <?php else: ?>
                    <!-- Controls -->
                    <div class="trip-controls">
                        <input
                            type="text"
                            id="tripSearchInput"
                            class="trip-search-input"
                            placeholder="&#x27BE; Search by Trip ID, Route, Card, Status..."
                            oninput="filterTrips()"
                        >
                        <select id="tripStatusFilter" class="trip-filter-select" onchange="filterTrips()">
                            <option value="">All Statuses</option>
                            <option value="completed">Completed</option>
                            <option value="in-progress">In Progress</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="assisted">Assisted</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>

                    <!-- Table -->
                    <div class="trip-table-wrapper" id="tripTableWrapper">
                        <table class="trip-table" id="tripTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Trip ID</th>
                                    <th>Route</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="tripTableBody">
                                <?php foreach ($tripHistory as $i => $trip):
                                    $status     = strtolower(trim($trip['trip_status'] ?? ''));
                                    
                                    $timestamp = !empty($trip['trip_timestamp'])
                                                  ? date('M d, Y h:i A', strtotime($trip['trip_timestamp']))
                                                  : '—';

                                    $fromLoc = htmlspecialchars($trip['from_location'] ?? '');
                                    $toLoc   = htmlspecialchars($trip['to_location']   ?? '');
                                ?>
                                <tr
                                    data-trip-id="<?php echo htmlspecialchars($trip['trip_id'] ?? ''); ?>"
                                    data-route="<?php echo htmlspecialchars($trip['route_id']  ?? ''); ?>"
                                    data-card="<?php echo htmlspecialchars($trip['card_id']    ?? ''); ?>"
                                    data-status="<?php echo htmlspecialchars($status); ?>"
                                    data-from="<?php echo htmlspecialchars($trip['from_location'] ?? ''); ?>"
                                    data-to="<?php echo htmlspecialchars($trip['to_location'] ?? ''); ?>"
                                    data-timestamp="<?php echo htmlspecialchars($timestamp); ?>"
                                >
                                    <td><?php echo $i + 1; ?></td>
                                    <td><code><?php echo htmlspecialchars($trip['trip_id'] ?? '—'); ?></code></td>
                                    <td>
                                        <?php if ($fromLoc !== '' && $toLoc !== ''): ?>
                                            <div class="route-cell">
                                                <span class="route-loc"><?php echo $fromLoc; ?></span>
                                                <span class="route-arrow"><i class="fas fa-arrow-right"></i></span>
                                                <span class="route-loc"><?php echo $toLoc; ?></span>
                                            </div>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($trip['route_id'] ?? '—'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $timestamp; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="trip-scroll-loading" id="scrollLoading">
                            <i class="fas fa-spinner"></i> Loading more...
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- ── End Trip History Modal ─────────────────────────────────────────── -->

    <!-- Fullscreen Image Modal -->
    <div id="imageModal" class="image-modal" onclick="closeFullscreen()">
        <span class="modal-close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <!-- Profile Zoom Modal -->
    <div id="profileZoomModal" class="profile-zoom-modal" role="dialog" aria-modal="true" aria-label="Profile photo">
        <div class="profile-zoom-inner">
            <button class="profile-zoom-close" onclick="closeProfileZoom()" aria-label="Close">&times;</button>
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <?php
    $activePage = 'dashboard';
    include __DIR__ . '/components/bottom_navbar.php';
    ?>

    <script src="<?php echo htmlspecialchars($basePath); ?>assets/script/driver/dashboard.js"></script>

    <script>
      // ── Profile Zoom ──────────────────────────────────────────────────────
      function openProfileZoom(type, value, name) {
        var modal    = document.getElementById('profileZoomModal');
        var inner    = modal.querySelector('.profile-zoom-inner');
        var closeBtn = inner.querySelector('.profile-zoom-close');
        inner.innerHTML = '';
        inner.appendChild(closeBtn);
        if (type === 'img') {
          var img = document.createElement('img');
          img.src = value; img.alt = name || 'Profile Photo';
          img.className = 'profile-zoom-img';
          inner.appendChild(img);
        } else {
          var ph = document.createElement('div');
          ph.className = 'profile-zoom-placeholder';
          ph.textContent = value;
          inner.appendChild(ph);
        }
        if (name) {
          var nm = document.createElement('div');
          nm.className = 'profile-zoom-name';
          nm.textContent = name;
          inner.appendChild(nm);
        }
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
      }
      function closeProfileZoom() {
        document.getElementById('profileZoomModal').classList.remove('open');
        document.body.style.overflow = '';
      }
      document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('profileZoomModal');
        if (!modal) return;
        modal.addEventListener('click', function (e) { if (e.target === modal) closeProfileZoom(); });
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && modal.classList.contains('open')) closeProfileZoom();
        });
      });

      // ── Trip History Modal ────────────────────────────────────────────────
      function openTripHistoryModal() {
        var modal = document.getElementById('tripHistoryModal');
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        
        // Initialize table if not already done
        if (allRows.length === 0) {
          allRows = getAllRows();
          visibleRows = allRows.slice();
          showRowsUpTo(ROWS_PER_SCROLL);
        }
      }
      
      function closeTripHistoryModal() {
        var modal = document.getElementById('tripHistoryModal');
        modal.classList.remove('open');
        document.body.style.overflow = '';
      }
      
      // Close modal when clicking outside content
      document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('tripHistoryModal');
        if (!modal) return;
        modal.addEventListener('click', function (e) {
          if (e.target === modal) closeTripHistoryModal();
        });
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape' && modal.classList.contains('open')) closeTripHistoryModal();
        });
      });

      // ── Trip Table: Search, Filter & Infinite Scroll ──────────────────────
      var ROWS_PER_SCROLL = 10;
      var displayedCount = 0;
      var visibleRows = [];
      var allRows = [];
      var isLoading = false;

      function getAllRows() {
        return Array.from(document.querySelectorAll('#tripTableBody tr'));
      }

      function updateRowNumbers() {
        visibleRows.forEach(function (row, idx) {
          var fc = row.querySelector('td:first-child');
          if (fc) fc.textContent = idx + 1;
        });
      }

      function showRowsUpTo(count) {
        visibleRows.forEach(function (row, idx) {
          row.style.display = idx < count ? '' : 'none';
        });
        displayedCount = Math.min(count, visibleRows.length);
        
        // Show/hide loading indicator
        var loadingEl = document.getElementById('scrollLoading');
        if (loadingEl) {
          if (displayedCount >= visibleRows.length) {
            loadingEl.classList.remove('show');
          } else {
            loadingEl.classList.add('show');
          }
        }
      }

      function filterTrips() {
        var search = (document.getElementById('tripSearchInput')  || {value:''}).value.toLowerCase().trim();
        var status = (document.getElementById('tripStatusFilter') || {value:''}).value.toLowerCase();

        visibleRows = allRows.filter(function (row) {
          var tripId    = (row.dataset.tripId || '').toLowerCase();
          var route     = (row.dataset.route  || '').toLowerCase();
          var card      = (row.dataset.card   || '').toLowerCase();
          var rStatus   = (row.dataset.status || '').toLowerCase();
          var fromLoc   = (row.dataset.from || '').toLowerCase();
          var toLoc     = (row.dataset.to || '').toLowerCase();
          var timestamp = (row.dataset.timestamp || '').toLowerCase();

          // Search across all fields
          var matchSearch = !search || 
                            tripId.includes(search) || 
                            route.includes(search) || 
                            card.includes(search) ||
                            fromLoc.includes(search) ||
                            toLoc.includes(search) ||
                            timestamp.includes(search);
          
          var matchStatus = !status || rStatus === status;
          return matchSearch && matchStatus;
        });

        // Hide all first
        allRows.forEach(function (r) { r.style.display = 'none'; });
        
        // Reset and show first batch
        displayedCount = 0;
        updateRowNumbers();
        showRowsUpTo(ROWS_PER_SCROLL);
      }

      function loadMoreOnScroll() {
        if (isLoading) return;
        
        var wrapper = document.getElementById('tripTableWrapper');
        if (!wrapper) return;
        
        var scrollTop = wrapper.scrollTop;
        var scrollHeight = wrapper.scrollHeight;
        var clientHeight = wrapper.clientHeight;
        
        // Load more when user scrolls to bottom (with 50px threshold)
        if (scrollTop + clientHeight >= scrollHeight - 50) {
          if (displayedCount < visibleRows.length) {
            isLoading = true;
            
            // Simulate small delay for better UX
            setTimeout(function() {
              showRowsUpTo(displayedCount + ROWS_PER_SCROLL);
              isLoading = false;
            }, 200);
          }
        }
      }

      // Initialize
      document.addEventListener('DOMContentLoaded', function () {
        var wrapper = document.getElementById('tripTableWrapper');
        if (wrapper) {
          wrapper.addEventListener('scroll', loadMoreOnScroll);
        }
      });

      // Expose filter function globally
      window.filterTrips = filterTrips;
    </script>
</body>

</html>