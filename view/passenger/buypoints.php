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

// Predefined reload amounts
$reloadAmounts = [50, 100, 200, 500, 1000, 2000];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Points - E-JEEP</title>
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/index.css" rel="stylesheet" type="text/css">
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/dashboard.css" rel="stylesheet" type="text/css">
    <link href="<?php echo htmlspecialchars($basePath); ?>assets/style/driver.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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

        .balance-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, #2c3e50 100%);
            padding: 25px;
            margin-bottom: 30px;
            color: white;
            box-shadow: 0 4px 15px rgba(26, 35, 50, 0.2);
        }

        .balance-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .balance-amount {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .balance-card-number {
            font-size: 0.85rem;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .amount-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .amount-option {
            padding: 20px;
            border: 2px solid #e9ecef;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--bg-white);
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .amount-option:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .amount-option.selected {
            border-color: var(--primary-color);
            background-color: #f0f7ff;
            box-shadow: 0 4px 12px rgba(26, 35, 50, 0.15);
        }

        .amount-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .amount-label {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .custom-amount-section {
            margin-bottom: 25px;
        }

        .custom-amount-input {
            position: relative;
        }

        .currency-symbol {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .custom-amount-input input {
            padding-left: 45px;
            font-size: 1.2rem;
            font-weight: 600;
        }

        /* Responsive Design for Mobile */
        @media (max-width: 768px) {
            .amount-options {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                margin-bottom: 20px;
            }

            .amount-option {
                padding: 15px 10px;
                min-height: 90px;
            }

            .amount-value {
                font-size: 1.1rem;
            }

            .amount-label {
                font-size: 0.75rem;
            }

            .custom-amount-input input {
                font-size: 1rem;
                padding-left: 40px;
            }

            .currency-symbol {
                font-size: 1rem;
                left: 12px;
            }
        }

        @media (max-width: 480px) {
            .amount-options {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-bottom: 20px;
            }

            .amount-option {
                padding: 12px 8px;
                min-height: 80px;
            }

            .amount-value {
                font-size: 1rem;
                margin-bottom: 3px;
            }

            .amount-label {
                font-size: 0.7rem;
            }

            .balance-amount {
                font-size: 2rem;
            }

            .balance-card {
                padding: 20px;
            }

            .custom-amount-input input {
                font-size: 0.95rem;
                padding: 12px 12px 12px 38px;
            }

            .currency-symbol {
                font-size: 0.95rem;
                left: 10px;
            }
        }

        .payment-methods {
            margin-bottom: 25px;
        }

        .payment-method {
            padding: 15px;
            border: 2px solid #e9ecef;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--bg-white);
        }

        .payment-method:hover {
            border-color: var(--primary-color);
        }

        .payment-method.selected {
            border-color: var(--primary-color);
            background-color: #f0f7ff;
        }

        .payment-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background-color: #f5f5f5;
        }

        .payment-details {
            flex: 1;
        }

        .payment-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 3px;
        }

        .payment-desc {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .payment-radio {
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 50%;
            position: relative;
        }

        .payment-method.selected .payment-radio {
            border-color: var(--primary-color);
        }

        .payment-method.selected .payment-radio::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            background-color: var(--primary-color);
            border-radius: 50%;
        }

        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            padding: 15px;
            margin-bottom: 25px;
        }

        .info-box-title {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box-text {
            font-size: 0.9rem;
            color: var(--text-light);
            line-height: 1.6;
        }
    </style>
</head>

<body>
    <!-- Dashboard Header at Top -->
    <div class="dashboard-header-top">
        <div class="dashboard-header-content">
            <div class="dashboard-header-text">
                <h1 class="dashboard-title">Buy Points</h1>
                <p class="dashboard-subtitle">Reload your E-JEEP card balance</p>
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
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <strong>Success!</strong> <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['pending'])): ?>
                <div class="alert alert-warning">
                    <strong>Processing:</strong> <?php echo htmlspecialchars($_GET['pending']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['info'])): ?>
                <div class="alert" style="background-color: #e7f3ff; border: 1px solid #b3d7ff; color: #0056b3;">
                    <strong>Info:</strong> <?php echo htmlspecialchars($_GET['info']); ?>
                </div>
            <?php endif; ?>

            <!-- Balance Card -->
            <div class="balance-card">
                <div class="balance-label">Current Balance</div>
                <div class="balance-amount">₱<?php echo number_format($passengerInfo['card_balance'] ?? 0.00, 2); ?></div>
                <div class="balance-card-number">
                    <i class="fas fa-id-card"></i>
                    Card: <?php echo $passengerInfo['card_number'] ? htmlspecialchars($passengerInfo['card_number']) : 'Not Issued'; ?>
                </div>
            </div>

            <!-- Info Box -->
            <div class="info-box">
                <div class="info-box-title">
                    <i class="fas fa-info-circle"></i>
                    How to Reload
                    <?php if (defined('PAYMONGO_MODE') && PAYMONGO_MODE === 'test'): ?>
                        <span style="background: #ff6b35; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; margin-left: 10px;">TEST MODE</span>
                    <?php endif; ?>
                </div>
                <div class="info-box-text">
                    Select an amount below and choose your preferred payment method. You will be redirected to a secure PayMongo checkout page to complete your payment. Your balance will be updated after successful payment. Minimum reload amount is ₱50.00.
                    <?php if (defined('PAYMONGO_MODE') && PAYMONGO_MODE === 'test'): ?>
                        <br><br><strong>🧪 Test Mode:</strong> You can use test payment methods. No real money will be charged.
                    <?php endif; ?>
                </div>
            </div>

            <form action="<?php echo htmlspecialchars($basePath); ?>controller/passenger/BuyPointsController.php" method="POST" id="buyPointsForm">
                <?php if (isset($dashboard_view)): ?>
                    <input type="hidden" name="dashboard_view" value="1">
                <?php endif; ?>

                <!-- Reload Amount Section -->
                <div class="dashboard-section">
                    <h2 class="section-title">Select Amount</h2>
                    
                    <!-- Predefined Amounts -->
                    <div class="amount-options">
                        <?php foreach ($reloadAmounts as $amount): ?>
                            <div class="amount-option" onclick="selectAmount(<?php echo $amount; ?>)">
                                <div class="amount-value">₱<?php echo number_format($amount, 2); ?></div>
                                <div class="amount-label">Quick Reload</div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Custom Amount -->
                    <div class="custom-amount-section">
                        <label for="custom_amount" class="form-label">Or Enter Custom Amount</label>
                        <div class="custom-amount-input">
                            <span class="currency-symbol">₱</span>
                            <input type="number" id="custom_amount" name="amount" class="form-input" placeholder="0.00" min="50" step="0.01" required>
                        </div>
                        <small class="form-hint">Minimum amount: ₱50.00</small>
                    </div>
                </div>

                <!-- Payment Method Section -->
                <div class="dashboard-section">
                    <h2 class="section-title">Payment Method</h2>
                    <div class="payment-methods">
                        <div class="payment-method selected" onclick="selectPaymentMethod('gcash')">
                            <div class="payment-icon" style="background-color: #0066cc; color: white;">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="payment-details">
                                <div class="payment-name">GCash</div>
                                <div class="payment-desc">Pay using GCash mobile wallet</div>
                            </div>
                            <div class="payment-radio"></div>
                            <input type="radio" name="payment_method" value="gcash" checked style="display: none;">
                        </div>

                        <div class="payment-method" onclick="selectPaymentMethod('paymaya')">
                            <div class="payment-icon" style="background-color: #00a859; color: white;">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="payment-details">
                                <div class="payment-name">PayMaya</div>
                                <div class="payment-desc">Pay using PayMaya wallet</div>
                            </div>
                            <div class="payment-radio"></div>
                            <input type="radio" name="payment_method" value="paymaya" style="display: none;">
                        </div>

                        <div class="payment-method" onclick="selectPaymentMethod('bank')">
                            <div class="payment-icon" style="background-color: #1a2332; color: white;">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="payment-details">
                                <div class="payment-name">Bank Transfer</div>
                                <div class="payment-desc">BDO, BPI, Metrobank, etc.</div>
                            </div>
                            <div class="payment-radio"></div>
                            <input type="radio" name="payment_method" value="bank" style="display: none;">
                        </div>

                        <div class="payment-method" onclick="selectPaymentMethod('card')">
                            <div class="payment-icon" style="background-color: #6c757d; color: white;">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="payment-details">
                                <div class="payment-name">Credit/Debit Card</div>
                                <div class="payment-desc">Visa, Mastercard, JCB</div>
                            </div>
                            <div class="payment-radio"></div>
                            <input type="radio" name="payment_method" value="card" style="display: none;">
                        </div>

                        <div class="payment-method" onclick="selectPaymentMethod('grab_pay')">
                            <div class="payment-icon" style="background-color: #00b14f; color: white;">
                                <i class="fas fa-car"></i>
                            </div>
                            <div class="payment-details">
                                <div class="payment-name">GrabPay</div>
                                <div class="payment-desc">Pay using GrabPay wallet</div>
                            </div>
                            <div class="payment-radio"></div>
                            <input type="radio" name="payment_method" value="grab_pay" style="display: none;">
                        </div>

                        <div class="payment-method" onclick="selectPaymentMethod('qrph')">
                            <div class="payment-icon" style="background-color: #1f4788; color: white;">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div class="payment-details">
                                <div class="payment-name">QRPh</div>
                                <div class="payment-desc">QR Philippines - Scan to pay</div>
                            </div>
                            <div class="payment-radio"></div>
                            <input type="radio" name="payment_method" value="qrph" style="display: none;">
                        </div>

                        <div class="payment-method" onclick="selectPaymentMethod('billease')">
                            <div class="payment-icon" style="background-color: #ff6b35; color: white;">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="payment-details">
                                <div class="payment-name">Billease</div>
                                <div class="payment-desc">Buy now, pay later</div>
                            </div>
                            <div class="payment-radio"></div>
                            <input type="radio" name="payment_method" value="billease" style="display: none;">
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-submit" id="submitBtn">
                        <i class="fas fa-shield-alt"></i> Pay Securely with Ejeeps
                    </button>
                    <a href="<?php echo isset($dashboard_view) ? 'index.php' : '../../view/passenger/dashboard.php'; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <?php
    $activePage = 'buypoints';
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

        let selectedAmount = null;

        function selectAmount(amount) {
            selectedAmount = amount;
            // Update visual selection
            document.querySelectorAll('.amount-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Set the input value
            document.getElementById('custom_amount').value = amount;
        }

        function selectPaymentMethod(method) {
            // Update visual selection
            document.querySelectorAll('.payment-method').forEach(methodEl => {
                methodEl.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Set the radio button
            const radio = event.currentTarget.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
        }

        // Form validation
        document.getElementById('buyPointsForm').addEventListener('submit', function(e) {
            const amount = document.getElementById('custom_amount').value;
            const submitBtn = document.getElementById('submitBtn');
            
            if (!amount || parseFloat(amount) < 50) {
                e.preventDefault();
                alert('Please enter a minimum amount of ₱50.00');
                return false;
            }
            
            if (parseFloat(amount) > 10000) {
                e.preventDefault();
                alert('Maximum reload amount is ₱10,000.00');
                return false;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            // Re-enable button after 30 seconds (in case of issues)
            setTimeout(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-shield-alt"></i> Pay Securely with PayMongo';
            }, 30000);
        });

        // Auto-select amount when typing in custom input
        document.getElementById('custom_amount').addEventListener('input', function() {
            const amount = parseFloat(this.value);
            if (amount > 0) {
                // Remove selection from predefined amounts
                document.querySelectorAll('.amount-option').forEach(option => {
                    option.classList.remove('selected');
                });
                selectedAmount = amount;
            }
        });
    </script>
</body>
</html>