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

// Get transactions (placeholder - you can create a proper transactions table later)
try {
    // For now, we'll show an empty list. You can add a transactions table later
    $transactions = [];
    // Example query structure (uncomment when transactions table exists):
    // $stmt = $pdo->prepare("SELECT * FROM transactions WHERE passenger_id = ? ORDER BY created_at DESC LIMIT 50");
    // $stmt->execute([$_SESSION['user_id']]);
    // $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    $transactions = [];
    error_log("Error fetching transactions: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#16a34a">
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
            <!-- Balance Card -->
            <div class="dashboard-card" style="margin-bottom: 25px;">
                <div class="card-icon"><i class="fas fa-wallet"></i></div>
                <h3 class="card-title">Current Balance</h3>
                <p class="card-value" style="font-size: 2rem; margin-top: 10px;">₱<?php echo number_format($passengerInfo['card_balance'] ?? 0.00, 2); ?></p>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <div class="filter-tab active" onclick="filterTransactions('all')">All</div>
                <div class="filter-tab" onclick="filterTransactions('payment')">Payments</div>
                <div class="filter-tab" onclick="filterTransactions('reload')">Reloads</div>
                <div class="filter-tab" onclick="filterTransactions('refund')">Refunds</div>
            </div>

            <!-- Transactions List -->
            <div id="transactionsList">
                <?php if (empty($transactions)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-receipt"></i></div>
                        <div class="empty-state-text">No transactions yet</div>
                        <div class="empty-state-subtext">Your transaction history will appear here once you start using your E-JEEP card</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <div class="transaction-card" data-type="<?php echo htmlspecialchars($transaction['type'] ?? 'payment'); ?>">
                            <div class="transaction-header">
                                <div class="transaction-type">
                                    <div class="transaction-icon <?php echo htmlspecialchars($transaction['type'] ?? 'payment'); ?>">
                                        <?php 
                                        $icon = 'fa-credit-card';
                                        if (isset($transaction['type'])) {
                                            if ($transaction['type'] === 'reload') $icon = 'fa-coins';
                                            elseif ($transaction['type'] === 'refund') $icon = 'fa-undo';
                                        }
                                        ?>
                                        <i class="fas <?php echo $icon; ?>"></i>
                                    </div>
                                    <div class="transaction-details">
                                        <div class="transaction-title"><?php echo htmlspecialchars($transaction['description'] ?? 'Transaction'); ?></div>
                                        <div class="transaction-date">
                                            <?php echo isset($transaction['created_at']) ? date('M d, Y h:i A', strtotime($transaction['created_at'])) : 'N/A'; ?>
                                        </div>
                                        <?php if (isset($transaction['status'])): ?>
                                            <span class="transaction-status <?php echo htmlspecialchars($transaction['status']); ?>">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="transaction-amount <?php echo (isset($transaction['amount']) && $transaction['amount'] > 0) ? 'positive' : 'negative'; ?>">
                                    <?php 
                                    $amount = $transaction['amount'] ?? 0;
                                    $prefix = $amount > 0 ? '+' : '';
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
            transactions.forEach(card => {
                if (type === 'all' || card.dataset.type === type) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });

            // Show empty state if no transactions match
            const visibleTransactions = Array.from(transactions).filter(card => card.style.display !== 'none');
            let emptyState = document.querySelector('.empty-state');
            if (visibleTransactions.length === 0 && transactions.length > 0) {
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = `
                        <div class="empty-state-icon"><i class="fas fa-filter"></i></div>
                        <div class="empty-state-text">No ${type} transactions found</div>
                        <div class="empty-state-subtext">Try selecting a different filter</div>
                    `;
                    document.getElementById('transactionsList').appendChild(emptyState);
                }
            } else if (emptyState && visibleTransactions.length > 0) {
                emptyState.remove();
            }
        }
    </script>
</body>
</html>