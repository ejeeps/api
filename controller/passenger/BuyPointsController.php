<?php
// Database connection using PDO
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../services/PayMongoService.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a passenger
if (!isset($_SESSION['user_id']) || $_SESSION['user_level'] !== 'passenger') {
    header("Location: ../../index.php?login=1&error=" . urlencode("Please login to access this page."));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $userId = $_SESSION['user_id'];

        // Validate required fields
        if (empty($_POST['amount']) || empty($_POST['payment_method'])) {
            throw new Exception("Please fill in all required fields.");
        }

        $amount = floatval($_POST['amount']);

        // Validate amount
        if ($amount < 50) {
            throw new Exception("Minimum reload amount is ₱50.00.");
        }

        if ($amount > 10000) {
            throw new Exception("Maximum reload amount is ₱10,000.00.");
        }

        $paymentMethod = $_POST['payment_method'];

        // Initialize PayMongo service
        $payMongoService = new PayMongoService();

        // Create transaction reference
        $transactionRef = generateTransactionReference($userId);

        // Start database transaction
        $pdo->beginTransaction();

        try {
            // Get user information for metadata
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
                "Points top-up via PayMongo - {$paymentMethod}"
            ]);

            $transactionId = $pdo->lastInsertId();

            // Prepare metadata for PayMongo (all values must be strings)
            $metadata = [
                'user_id' => (string)$userId,
                'transaction_reference' => $transactionRef,
                'transaction_id' => (string)$transactionId,
                'customer_email' => $userInfo['email'],
                'customer_name' => $userInfo['first_name'] . ' ' . $userInfo['last_name'],
                'payment_method' => $paymentMethod
            ];

            // Create PayMongo payment intent
            $paymentIntent = $payMongoService->createPaymentIntent(
                $amount,
                'PHP',
                "Points Top-up - ₱" . number_format($amount, 2),
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

            // Detect mobile device
            $isMobile = preg_match('/(android|iphone|ipad|mobile)/i', $_SERVER['HTTP_USER_AGENT'] ?? '');
            
            // Create checkout session (use mobile optimization if on mobile)
            $checkoutSession = $payMongoService->createCheckoutSession(
                $paymentIntent['id'], 
                null, 
                null, 
                $amount, 
                $isMobile
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

            // Store transaction reference in session for callback handling
            $_SESSION['pending_transaction'] = $transactionRef;

            // Redirect to PayMongo checkout
            header("Location: " . $checkoutSession['attributes']['checkout_url']);
            exit();

        } catch (Exception $e) {
            // Rollback database transaction on error
            $pdo->rollBack();
            throw $e;
        }

    } catch (Exception $e) {
        // Log the error
        logPayMongoTransaction('Buy Points Error', [
            'user_id' => $userId ?? null,
            'error' => $e->getMessage(),
            'amount' => $amount ?? null
        ]);

        // Redirect back with error message
        $errorMessage = urlencode($e->getMessage());
        if (isset($_POST['dashboard_view']) || isset($_GET['dashboard_view'])) {
            header("Location: ../../index.php?page=buypoints&error=" . $errorMessage);
        } else {
            header("Location: ../../view/passenger/buypoints.php?error=" . $errorMessage);
        }
        exit();
    }
} else {
    // If not POST request, redirect to buy points page
    if (isset($_GET['dashboard_view'])) {
        header("Location: ../../index.php?page=buypoints");
    } else {
        header("Location: ../../view/passenger/buypoints.php");
    }
    exit();
}
?>

