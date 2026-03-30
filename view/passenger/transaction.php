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

// Get transactions from database
$transactions = [];
try {
    // Get passenger's card_id first (use passenger_table_id from getPassengerInfo)
    $passengerId = $passengerInfo['passenger_table_id'] ?? $passengerInfo['id'];
    $cardStmt = $pdo->prepare("SELECT c.id as card_id FROM cards c 
                               INNER JOIN card_assign_passengers cap ON c.id = cap.card_id 
                               WHERE cap.passenger_id = ? AND cap.assignment_status = 'active' 
                               LIMIT 1");
    $cardStmt->execute([$passengerId]);
    $cardData = $cardStmt->fetch(PDO::FETCH_ASSOC);
    $cardId = $cardData ? $cardData['card_id'] : null;
    
    // Fetch wallet/payment transactions
    $walletTransactions = [];
    if ($cardId) {
        $stmt = $pdo->prepare("SELECT 
            id,
            transaction_reference as reference,
            amount,
            transaction_type as type,
            status,
            description,
            created_at,
            payment_method
        FROM transactions 
        WHERE (user_id = ? OR card_id = ?)
          AND transaction_type <> 'refund'
        ORDER BY created_at DESC 
        LIMIT 50");
        $stmt->execute([$_SESSION['user_id'], $cardId]);
        $walletTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT 
            id,
            transaction_reference as reference,
            amount,
            transaction_type as type,
            status,
            description,
            created_at,
            payment_method
        FROM transactions 
        WHERE user_id = ?
          AND transaction_type <> 'refund'
        ORDER BY created_at DESC 
        LIMIT 50");
        $stmt->execute([$_SESSION['user_id']]);
        $walletTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Normalize wallet records for UI
    foreach ($walletTransactions as &$transaction) {
        $transaction['source'] = 'wallet';
        $transaction['trip_id'] = null;
        $transaction['previous_balance'] = null;
        $transaction['new_balance'] = null;
        $transaction['route_name'] = null;

        // Map database types to display types
        switch ($transaction['type']) {
            case 'purchase':
                $transaction['display_type'] = 'payment';
                $transaction['description'] = $transaction['description'] ?: 'E-JEEP Card Payment';
                break;
            case 'top_up':
                $transaction['display_type'] = 'reload';
                $transaction['description'] = $transaction['description'] ?: 'Card Reload';
                break;
            case 'refund':
                $transaction['display_type'] = 'payment';
                $transaction['description'] = $transaction['description'] ?: 'E-JEEP Card Payment';
                break;
            default:
                $transaction['display_type'] = 'payment';
        }
    }
    unset($transaction);

    // Fetch trip fare deductions (ride receipts) using card_id_number
    $rideTransactions = [];
    $cardNumber = $passengerInfo['card_number'] ?? null;
    if (!empty($cardNumber)) {
        $tripStmt = $pdo->prepare("SELECT
                tt.id,
                tt.trip_id,
                tt.amount,
                tt.type,
                tt.status,
                tt.description,
                tt.previous_balance,
                tt.new_balance,
                tt.created_at,
                CONCAT(COALESCE(r.from_location, ''), CASE WHEN r.from_location IS NOT NULL AND r.to_location IS NOT NULL THEN ' → ' ELSE '' END, COALESCE(r.to_location, '')) AS route_name
            FROM trip_transactions tt
            LEFT JOIN trip_fares tf ON tf.id = tt.trip_fare_id
            LEFT JOIN routes r ON r.id = tf.route_id
            WHERE tt.card_id = ?
              AND tt.type <> 'refund'
            ORDER BY tt.created_at DESC
            LIMIT 50");
        $tripStmt->execute([(string)$cardNumber]);
        $rideTransactions = $tripStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Normalize trip deductions for same card UI format
    foreach ($rideTransactions as &$transaction) {
        $transaction['source'] = 'ride';
        $transaction['reference'] = 'TRIP-' . str_pad((string)$transaction['id'], 6, '0', STR_PAD_LEFT);
        $transaction['payment_method'] = 'card_balance';
        $transaction['display_type'] = 'payment';

        if ($transaction['type'] === 'adjustment') {
            $transaction['description'] = $transaction['description'] ?: 'Trip Fare Adjustment';
        } else {
            $transaction['type'] = 'purchase'; // keep existing amount sign logic compatible
            $transaction['description'] = $transaction['description'] ?: 'Trip Fare Deduction';
        }
    }
    unset($transaction);

    // Merge and sort by latest activity
    $transactions = array_merge($walletTransactions, $rideTransactions);
    usort($transactions, static function ($a, $b): int {
        return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
    });
    $transactions = array_slice($transactions, 0, 100);

} catch (PDOException $e) {
    $transactions = [];
    error_log("Error fetching transactions: " . $e->getMessage());
}
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
    <meta name="description" content="E-JEEP Transaction History">
    <link rel="manifest" href="<?php echo htmlspecialchars($basePath); ?>manifest.json">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($basePath); ?>assets/icons/icon-192.png">
    <title>Transaction History - E-JEEP</title>
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

        .transaction-card {
            background: var(--bg-white);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .transaction-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .transaction-type {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .transaction-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .transaction-icon.payment {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .transaction-icon.reload {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .transaction-icon.refund {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .transaction-details {
            flex: 1;
            margin-left: 15px;
        }

        .transaction-title {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 5px;
            font-size: 1rem;
        }

        .transaction-date {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .transaction-amount {
            font-size: 1.2rem;
            font-weight: 700;
            text-align: right;
        }

        .transaction-amount.positive {
            color: #388e3c;
        }

        .transaction-amount.negative {
            color: #d32f2f;
        }

        .transaction-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 8px;
        }

        .transaction-status.completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .transaction-status.pending {
            background-color: #fff3e0;
            color: #f57c00;
        }

        .transaction-status.failed {
            background-color: #ffebee;
            color: #d32f2f;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-light);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state-text {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .empty-state-subtext {
            font-size: 0.9rem;
            color: var(--text-light);
        }

        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 20px;
            border: 2px solid #e9ecef;
            background: var(--bg-white);
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
            transition: all 0.3s ease;
        }

        .filter-tab:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .filter-tab.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
    </style>
</head>

<body>
    <!-- Dashboard Header at Top -->
    <div class="dashboard-header-top">
        <div class="dashboard-header-content">
            <div class="dashboard-header-text">
                <h1 class="dashboard-title">Transaction History</h1>
                <p class="dashboard-subtitle">View your E-JEEP card transactions</p>
            </div>
            <div class="dashboard-profile-image">
                <?php
                    $fullName = htmlspecialchars($passengerInfo['first_name'] . ' ' . $passengerInfo['last_name']);
                    $initials = strtoupper(substr($passengerInfo['first_name'], 0, 1) . substr($passengerInfo['last_name'], 0, 1));
                ?>
                <?php if (!empty($passengerInfo['profile_image']) && file_exists($imageBasePath . $passengerInfo['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($imageBasePath . $passengerInfo['profile_image']); ?>" alt="Profile" class="profile-avatar" title="Click to view profile photo" onclick="openProfileZoom('img', '<?php echo htmlspecialchars($imageBasePath . $passengerInfo['profile_image']); ?>', '<?php echo $fullName; ?>')">
                <?php else: ?>
                    <div class="profile-avatar-placeholder" title="Click to view profile" onclick="openProfileZoom('initials', '<?php echo $initials; ?>', '<?php echo $fullName; ?>')">
                        <?php echo strtoupper(substr($passengerInfo['first_name'], 0, 1) . substr($passengerInfo['last_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
           

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="filter-tab active" onclick="filterTransactions('all')">All</div>
                <div class="filter-tab" onclick="filterTransactions('payment')">Payments</div>
                <div class="filter-tab" onclick="filterTransactions('reload')">Reloads</div>
            </div>

            <!-- Transactions List -->
            <div id="transactionsList">
                <?php if (empty($transactions)): ?>
                    <div class="empty-state" id="noTransactions">
                        <div class="empty-state-icon"><i class="fas fa-receipt"></i></div>
                        <div class="empty-state-text">No transactions yet</div>
                        <div class="empty-state-subtext">Your payment history will appear here once you make transactions with your E-JEEP card. <a href="<?php echo $basePath; ?>index.php?page=buypoints" style="color: var(--primary-color);">Buy points now</a></div>
                    </div>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <div class="transaction-card" data-type="<?php echo htmlspecialchars($transaction['display_type']); ?>">
                            <div class="transaction-header">
                                <div class="transaction-type">
                                    <div class="transaction-icon <?php echo htmlspecialchars($transaction['display_type']); ?>">
                                        <?php 
                                        $icon = 'fa-credit-card';
                                        if ($transaction['display_type'] === 'reload') $icon = 'fa-coins';
                                        elseif ($transaction['display_type'] === 'refund') $icon = 'fa-undo';
                                        ?>
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="transaction-details">
                                        <div class="transaction-title"><?php echo htmlspecialchars($transaction['description']); ?></div>
                                        <div class="transaction-date">
                                            <?php echo date('M d, Y h:i A', strtotime($transaction['created_at'])); ?>
                                        </div>
                                        <?php if (!empty($transaction['trip_id'])): ?>
                                            <div class="transaction-date">
                                                Receipt: <?php echo htmlspecialchars((string)$transaction['trip_id']); ?>
                                                <?php if (!empty($transaction['route_name'])): ?>
                                                    · <?php echo htmlspecialchars((string)$transaction['route_name']); ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($transaction['previous_balance'], $transaction['new_balance']) && $transaction['previous_balance'] !== null && $transaction['new_balance'] !== null): ?>
                                            <div class="transaction-date">
                                                Balance: ₱<?php echo number_format((float)$transaction['previous_balance'], 2); ?> → ₱<?php echo number_format((float)$transaction['new_balance'], 2); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($transaction['status']): ?>
                                            <span class="transaction-status <?php echo htmlspecialchars($transaction['status']); ?>">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="transaction-amount <?php echo ($transaction['type'] === 'purchase') ? 'negative' : 'positive'; ?>">
                                    <?php 
                                    $amount = $transaction['amount'] ?? 0;
                                    // Purchases are negative (money out), top_ups/refunds are positive (money in)
                                    $prefix = ($transaction['type'] === 'purchase') ? '-' : '+';
                                    echo $prefix . '₱' . number_format(abs($amount), 2); 
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php include 'view/components/live_bus_tracker.php'; ?>
    <!-- Bottom Navigation Bar -->
    <?php
    $activePage = 'transaction';
    include __DIR__ . '/components/bottom_navbar.php';
    ?>

    <!-- Profile Zoom Modal -->
    <div id="profileZoomModal" class="profile-zoom-modal" role="dialog" aria-modal="true" aria-label="Profile photo">
        <div class="profile-zoom-inner">
            <button class="profile-zoom-close" onclick="closeProfileZoom()" aria-label="Close">&times;</button>
        </div>
    </div>

    <script>
        // ── Profile Zoom Feature ──────────────────────────────────────────────
        function openProfileZoom(type, value, name) {
            var modal    = document.getElementById('profileZoomModal');
            var inner    = modal.querySelector('.profile-zoom-inner');
            var closeBtn = inner.querySelector('.profile-zoom-close');
            inner.innerHTML = '';
            inner.appendChild(closeBtn);
            if (type === 'img') {
                var img       = document.createElement('img');
                img.src       = value;
                img.alt       = name || 'Profile Photo';
                img.className = 'profile-zoom-img';
                inner.appendChild(img);
            } else {
                var ph         = document.createElement('div');
                ph.className   = 'profile-zoom-placeholder';
                ph.textContent = value;
                inner.appendChild(ph);
            }
            if (name) {
                var nameEl         = document.createElement('div');
                nameEl.className   = 'profile-zoom-name';
                nameEl.textContent = name;
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
            var pModal = document.getElementById('profileZoomModal');
            if (pModal) {
                pModal.addEventListener('click', function (e) {
                    if (e.target === pModal) closeProfileZoom();
                });
            }
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && pModal && pModal.classList.contains('open')) closeProfileZoom();
            });
        });
        // ─────────────────────────────────────────────────────────────────────

        function filterTransactions(type) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');

            // Filter transactions
            const transactions = document.querySelectorAll('.transaction-card');
            const noTransactionsMsg = document.getElementById('noTransactions');
            
            transactions.forEach(card => {
                if (type === 'all' || card.dataset.type === type) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });

            // Show/hide empty state based on filter
            const visibleTransactions = Array.from(transactions).filter(card => card.style.display !== 'none');
            let filterEmptyState = document.getElementById('filterEmptyState');
            
            if (visibleTransactions.length === 0 && transactions.length > 0) {
                // Hide the original "no transactions" message
                if (noTransactionsMsg) noTransactionsMsg.style.display = 'none';
                
                // Show filter-specific empty state
                if (!filterEmptyState) {
                    filterEmptyState = document.createElement('div');
                    filterEmptyState.id = 'filterEmptyState';
                    filterEmptyState.className = 'empty-state';
                    document.getElementById('transactionsList').appendChild(filterEmptyState);
                }
                
                const typeLabel = type.charAt(0).toUpperCase() + type.slice(1);
                filterEmptyState.innerHTML = `
                    <div class="empty-state-icon"><i class="fas fa-filter"></i></div>
                    <div class="empty-state-text">No ${typeLabel} transactions found</div>
                    <div class="empty-state-subtext">You don't have any ${type} transactions yet. Try selecting "All" to see your complete history.</div>
                `;
                filterEmptyState.style.display = 'block';
                
            } else {
                // Hide filter empty state and show original if needed
                if (filterEmptyState) filterEmptyState.style.display = 'none';
                if (noTransactionsMsg && transactions.length === 0) noTransactionsMsg.style.display = 'block';
            }
        }
    </script>
</body>
</html>