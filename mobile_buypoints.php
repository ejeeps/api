<?php
/**
 * Mobile-Optimized Buy Points Page
 * This page is specifically designed for mobile users who have issues with payment method visibility
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'passenger') {
    header("Location: index.php?login=1&error=" . urlencode("Please login to access this page."));
    exit;
}

require_once __DIR__ . '/config/connection.php';
require_once __DIR__ . '/controller/passenger/get_passengers_info.php';
require_once __DIR__ . '/services/PayMongoService.php';

$passengerInfo = getPassengerInfo($pdo, $_SESSION['user_id']);
if (!$passengerInfo) {
    header("Location: index.php?login=1&error=" . urlencode("Passenger information not found."));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $userId = $_SESSION['user_id'];
        $amount = floatval($_POST['amount']);
        $paymentMethod = $_POST['payment_method'];

        // Validate amount
        if ($amount < 50 || $amount > 10000) {
            throw new Exception("Amount must be between ₱50.00 and ₱10,000.00");
        }

        // Initialize PayMongo service
        $payMongoService = new PayMongoService();
        $transactionRef = generateTransactionReference($userId);

        // Start database transaction
        $pdo->beginTransaction();

        // Get user information
        $stmt = $pdo->prepare("
            SELECT u.email, u.first_name, u.last_name, p.id as passenger_id 
            FROM users u 
            JOIN passengers p ON u.id = p.user_id 
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userInfo) {
            throw new Exception("User information not found.");
        }

        // Create pending transaction record
        $stmt = $pdo->prepare("
            INSERT INTO transactions (
                user_id, transaction_reference, amount, transaction_type, 
                status, payment_method, description, created_at, updated_at
            ) VALUES (?, ?, ?, 'top_up', 'pending', 'paymongo', ?, NOW(), NOW())
        ");
        $stmt->execute([
            $userId, 
            $transactionRef, 
            $amount, 
            "Mobile Points top-up via PayMongo - {$paymentMethod}"
        ]);

        $transactionId = $pdo->lastInsertId();

        // Prepare metadata for PayMongo
        $metadata = [
            'user_id' => (string)$userId,
            'transaction_reference' => $transactionRef,
            'transaction_id' => (string)$transactionId,
            'customer_email' => $userInfo['email'],
            'customer_name' => $userInfo['first_name'] . ' ' . $userInfo['last_name'],
            'payment_method' => $paymentMethod,
            'mobile_checkout' => 'true'
        ];

        // Create PayMongo payment intent
        $paymentIntent = $payMongoService->createPaymentIntent(
            $amount,
            'PHP',
            "Mobile Points Top-up - ₱" . number_format($amount, 2),
            $metadata
        );

        if (!$paymentIntent) {
            throw new Exception("Failed to create payment intent. Please try again.");
        }

        // Update transaction with payment intent ID
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET payment_intent_id = ?, status = 'processing', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$paymentIntent['id'], $transactionId]);

        // Create mobile-optimized checkout session
        $checkoutSession = $payMongoService->createCheckoutSession(
            $paymentIntent['id'], 
            null, 
            null, 
            $amount, 
            true // Force mobile optimization
        );

        if (!$checkoutSession) {
            throw new Exception("Failed to create checkout session. Please try again.");
        }

        // Update transaction with checkout session ID
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET checkout_session_id = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$checkoutSession['id'], $transactionId]);

        // Commit database transaction
        $pdo->commit();

        // Store transaction reference in session
        $_SESSION['pending_transaction'] = $transactionRef;

        // Redirect to PayMongo checkout
        header("Location: " . $checkoutSession['attributes']['checkout_url']);
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $errorMessage = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Buy Points - E-JEEP</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 20px; background: #f5f5f5; 
        }
        .container { max-width: 400px; margin: 0 auto; }
        .card { 
            background: white; padding: 20px; border-radius: 12px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; 
        }
        .balance { 
            background: linear-gradient(135deg, #1665f8, #2c3e50); 
            color: white; text-align: center; padding: 25px; 
        }
        .balance-amount { font-size: 2rem; font-weight: bold; margin: 10px 0; }
        .form-group { margin-bottom: 20px; }
        .form-label { 
            display: block; margin-bottom: 8px; font-weight: 600; 
            color: #333; font-size: 14px; 
        }
        .form-input { 
            width: 100%; padding: 15px; border: 2px solid #e0e0e0; 
            border-radius: 8px; font-size: 16px; 
        }
        .form-input:focus { 
            outline: none; border-color: #1665f8; 
        }
        .amount-options { 
            display: grid; grid-template-columns: repeat(3, 1fr); 
            gap: 10px; margin-bottom: 20px; 
        }
        .amount-option { 
            padding: 15px 10px; border: 2px solid #e0e0e0; 
            border-radius: 8px; text-align: center; cursor: pointer; 
            background: white; transition: all 0.3s; 
        }
        .amount-option:hover, .amount-option.selected { 
            border-color: #1665f8; background: #f0f7ff; 
        }
        .payment-methods { margin-bottom: 20px; }
        .payment-method { 
            display: flex; align-items: center; padding: 15px; 
            border: 2px solid #e0e0e0; border-radius: 8px; 
            margin-bottom: 10px; cursor: pointer; transition: all 0.3s; 
        }
        .payment-method:hover, .payment-method.selected { 
            border-color: #1665f8; background: #f0f7ff; 
        }
        .payment-icon { 
            width: 40px; height: 40px; border-radius: 8px; 
            display: flex; align-items: center; justify-content: center; 
            margin-right: 15px; font-size: 18px; color: white; 
        }
        .payment-details { flex: 1; }
        .payment-name { font-weight: 600; margin-bottom: 3px; }
        .payment-desc { font-size: 12px; color: #666; }
        .btn-submit { 
            width: 100%; padding: 18px; background: #1665f8; 
            color: white; border: none; border-radius: 8px; 
            font-size: 16px; font-weight: 600; cursor: pointer; 
        }
        .btn-submit:hover { background: #0d4fd3; }
        .error { 
            background: #fee; color: #c33; padding: 15px; 
            border-radius: 8px; margin-bottom: 20px; 
        }
        .info { 
            background: #e7f3ff; color: #0056b3; padding: 15px; 
            border-radius: 8px; margin-bottom: 20px; font-size: 14px; 
        }
        .mobile-note { 
            background: #fff3cd; color: #856404; padding: 15px; 
            border-radius: 8px; margin-bottom: 20px; font-size: 14px; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card balance">
            <div>Current Balance</div>
            <div class="balance-amount">₱<?php echo number_format($passengerInfo['card_balance'] ?? 0.00, 2); ?></div>
            <div>Card: <?php echo $passengerInfo['card_number'] ? htmlspecialchars($passengerInfo['card_number']) : 'Not Issued'; ?></div>
        </div>

        <?php if (isset($errorMessage)): ?>
            <div class="error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <div class="mobile-note">
            <strong>📱 Mobile Optimized Checkout</strong><br>
            This page uses fewer payment methods to ensure all options are visible on mobile devices.
        </div>

        <div class="info">
            <strong>🧪 Test Mode Active</strong><br>
            No real money will be charged. Use test payment methods.
        </div>

        <form method="POST">
            <div class="card">
                <div class="form-group">
                    <label class="form-label">Select Amount</label>
                    <div class="amount-options">
                        <div class="amount-option" onclick="selectAmount(50)">₱50</div>
                        <div class="amount-option" onclick="selectAmount(100)">₱100</div>
                        <div class="amount-option" onclick="selectAmount(200)">₱200</div>
                        <div class="amount-option" onclick="selectAmount(500)">₱500</div>
                        <div class="amount-option" onclick="selectAmount(1000)">₱1000</div>
                        <div class="amount-option" onclick="selectAmount(2000)">₱2000</div>
                    </div>
                    <input type="number" name="amount" id="amount" class="form-input" 
                           placeholder="Enter custom amount" min="50" max="10000" step="0.01" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Method (Mobile Optimized)</label>
                    <div class="payment-methods">
                        <div class="payment-method selected" onclick="selectPayment('card', this)">
                            <div class="payment-icon" style="background: #6c757d;">💳</div>
                            <div class="payment-details">
                                <div class="payment-name">Credit/Debit Card</div>
                                <div class="payment-desc">Visa, Mastercard, JCB</div>
                            </div>
                            <input type="radio" name="payment_method" value="card" checked style="display:none;">
                        </div>
                        
                        <div class="payment-method" onclick="selectPayment('gcash', this)">
                            <div class="payment-icon" style="background: #0066cc;">📱</div>
                            <div class="payment-details">
                                <div class="payment-name">GCash</div>
                                <div class="payment-desc">Most popular mobile wallet</div>
                            </div>
                            <input type="radio" name="payment_method" value="gcash" style="display:none;">
                        </div>
                        
                        <div class="payment-method" onclick="selectPayment('paymaya', this)">
                            <div class="payment-icon" style="background: #00a859;">💰</div>
                            <div class="payment-details">
                                <div class="payment-name">PayMaya</div>
                                <div class="payment-desc">Digital wallet</div>
                            </div>
                            <input type="radio" name="payment_method" value="paymaya" style="display:none;">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    🔒 Pay Securely with PayMongo
                </button>
            </div>
        </form>

        <div class="info">
            <strong>💡 Mobile Tips:</strong><br>
            • This page uses only 3 payment methods for better mobile visibility<br>
            • All payment options should be clearly visible<br>
            • If you still don't see options, try opening in your default browser
        </div>
    </div>

    <script>
        function selectAmount(amount) {
            document.getElementById('amount').value = amount;
            document.querySelectorAll('.amount-option').forEach(el => el.classList.remove('selected'));
            event.target.classList.add('selected');
        }

        function selectPayment(method, element) {
            document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('selected'));
            element.classList.add('selected');
            element.querySelector('input[type="radio"]').checked = true;
        }

        document.getElementById('amount').addEventListener('input', function() {
            document.querySelectorAll('.amount-option').forEach(el => el.classList.remove('selected'));
        });
    </script>
</body>
</html>