<?php
/**
 * PayMongo Callback Controller
 * 
 * This controller handles the return URLs from PayMongo checkout sessions.
 * It processes both successful and cancelled payments.
 */

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../services/PayMongoService.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php?login=1&error=" . urlencode("Session expired. Please login again."));
    exit;
}

$userId = $_SESSION['user_id'];
$status = $_GET['status'] ?? 'unknown';

// Initialize PayMongo service
$payMongoService = new PayMongoService();

// Log callback received
logPayMongoTransaction('Payment Callback Received', [
    'user_id' => $userId,
    'status' => $status,
    'get_params' => $_GET,
    'session_transaction' => $_SESSION['pending_transaction'] ?? 'none'
]);

try {
    switch ($status) {
        case 'success':
            handleSuccessCallback($userId, $payMongoService);
            break;
            
        case 'cancel':
            handleCancelCallback($userId, $payMongoService);
            break;
            
        default:
            throw new Exception('Unknown payment status: ' . $status);
    }

} catch (Exception $e) {
    logPayMongoTransaction('Callback Processing Error', [
        'user_id' => $userId,
        'status' => $status,
        'error' => $e->getMessage()
    ]);

    // Redirect with error
    $errorMessage = urlencode($e->getMessage());
    header("Location: ../../index.php?page=buypoints&error=" . $errorMessage);
    exit;
}

/**
 * Handle successful payment callback
 */
function handleSuccessCallback($userId, $payMongoService) {
    try {
        // Initialize database connection
        $database = new Database();
        $pdo = $database->getConnection();
        $transaction = null;

        // Get checkout session ID from URL parameters
        $checkoutSessionId = $_GET['checkout_session_id'] ?? null;

        // Also check for payment_intent_id directly from URL (some payment methods return this)
        $paymentIntentId = $_GET['payment_intent_id'] ?? null;

        if (!$checkoutSessionId) {
            // If no checkout session ID, check for pending transaction in session
            $transactionRef = $_SESSION['pending_transaction'] ?? null;

            if ($transactionRef) {
                // Find the transaction by reference
                $stmt = $pdo->prepare("
                    SELECT id, payment_intent_id, checkout_session_id, status, amount, transaction_reference
                    FROM transactions
                    WHERE transaction_reference = ? AND user_id = ?
                    ORDER BY created_at DESC
                    LIMIT 1
                ");
                $stmt->execute([$transactionRef, $userId]);
                $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($transaction) {
                    $checkoutSessionId = $transaction['checkout_session_id'];
                    // If we don't have payment_intent_id from URL, get it from transaction
                    if (!$paymentIntentId) {
                        $paymentIntentId = $transaction['payment_intent_id'];
                    }
                }
            }
        } else {
            // We have checkout_session_id, find the transaction
            $stmt = $pdo->prepare("
                SELECT id, payment_intent_id, checkout_session_id, status, amount, transaction_reference
                FROM transactions
                WHERE checkout_session_id = ? AND user_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$checkoutSessionId, $userId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        // If we still don't have checkout_session_id but have payment_intent_id, try to find by payment_intent_id
        if (!$checkoutSessionId && $paymentIntentId && !$transaction) {
            $stmt = $pdo->prepare("
                SELECT id, payment_intent_id, checkout_session_id, status, amount, transaction_reference
                FROM transactions
                WHERE payment_intent_id = ? AND user_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$paymentIntentId, $userId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$checkoutSessionId && !$paymentIntentId) {
            throw new Exception('No checkout session or payment found. Please try again.');
        }

        // If we have checkout_session_id, get details from PayMongo
        if ($checkoutSessionId) {
            $checkoutSession = $payMongoService->getCheckoutSession($checkoutSessionId);

            if ($checkoutSession) {
                // Try to get payment_intent_id from checkout session if not already have it
                $sessionPaymentIntentId = $checkoutSession['attributes']['payment_intent']['data']['id'] ??
                                          $checkoutSession['attributes']['payment_intent_id'] ??
                                          null;

                if ($sessionPaymentIntentId && !$paymentIntentId) {
                    $paymentIntentId = $sessionPaymentIntentId;
                }
            }
        }

        // If still no payment_intent_id, get it from our database transaction record
        if (!$paymentIntentId && $transaction) {
            $paymentIntentId = $transaction['payment_intent_id'];
        }

        if (!$paymentIntentId) {
            throw new Exception('Payment intent ID not found. Please contact support.');
        }

        // Get payment intent details from PayMongo
        $paymentIntent = $payMongoService->getPaymentIntent($paymentIntentId);

        if (!$paymentIntent) {
            // If we can't get from PayMongo but have transaction record, check if already processed
            if ($transaction && $transaction['status'] === 'completed') {
                logPayMongoTransaction('Payment Already Processed', [
                    'user_id' => $userId,
                    'transaction_id' => $transaction['id'],
                    'payment_intent_id' => $paymentIntentId
                ]);

                unset($_SESSION['pending_transaction']);
                $successMessage = urlencode("Payment already processed! ₱" . number_format($transaction['amount'], 2) . " has been added to your account.");
                header("Location: ../../index.php?page=buypoints&success=" . $successMessage);
                exit;
            }
            throw new Exception('Failed to retrieve payment details from payment provider.');
        }

        $paymentStatus = $paymentIntent['attributes']['status'];
        $amount = convertToPesos($paymentIntent['attributes']['amount']);

        logPayMongoTransaction('Success Callback Processing', [
            'user_id' => $userId,
            'payment_intent_id' => $paymentIntentId,
            'payment_status' => $paymentStatus,
            'amount' => $amount,
            'transaction_id' => $transaction['id'] ?? null
        ]);

        if ($paymentStatus === 'succeeded') {
            // Payment was successful, process it
            $existingTransactionId = $transaction['id'] ?? null;
            $result = $payMongoService->processSuccessfulPayment($paymentIntentId, $userId, $existingTransactionId);

            if ($result['success']) {
                // Clear pending transaction from session
                unset($_SESSION['pending_transaction']);

                // Redirect with success message
                $successMessage = urlencode("Payment successful! ₱" . number_format($amount, 2) . " has been added to your account. New balance: ₱" . number_format($result['new_balance'], 2));
                header("Location: ../../index.php?page=buypoints&success=" . $successMessage);
                exit;
            } else {
                throw new Exception($result['error'] ?? 'Failed to process payment');
            }

        } elseif ($paymentStatus === 'processing' || $paymentStatus === 'awaiting_payment_method') {
            // Payment is still processing or awaiting confirmation
            // Update transaction status to processing if not already
            if ($transaction && $transaction['status'] === 'pending') {
                $stmt = $pdo->prepare("
                    UPDATE transactions
                    SET status = 'processing', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$transaction['id']]);
            }

            $pendingMessage = urlencode("Your payment is being processed. The balance will be updated once payment is confirmed. Please check back in a few minutes.");
            header("Location: ../../index.php?page=buypoints&pending=" . $pendingMessage);
            exit;

        } elseif ($paymentStatus === 'awaiting_next_action') {
            // Payment requires additional action (like 3D Secure)
            $pendingMessage = urlencode("Payment requires additional verification. Please complete the authentication process.");
            header("Location: ../../index.php?page=buypoints&info=" . $pendingMessage);
            exit;

        } else {
            // Payment failed or other status
            // Update transaction status to failed
            if ($transaction) {
                $stmt = $pdo->prepare("
                    UPDATE transactions
                    SET status = 'failed', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$transaction['id']]);
            }

            throw new Exception('Payment was not successful. Status: ' . $paymentStatus);
        }

    } catch (Exception $e) {
        logPayMongoTransaction('Success Callback Error', [
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}

/**
 * Handle cancelled payment callback
 */
function handleCancelCallback($userId, $payMongoService) {
    try {
        // Get transaction reference from session
        $transactionRef = $_SESSION['pending_transaction'] ?? null;
        
        if ($transactionRef) {
            // Resolve the transaction row so we can check the real provider status.
            // Users sometimes hit "cancel" / back even though PayMongo later marks the payment as succeeded.
            $database = new Database();
            $pdo = $database->getConnection();

            $stmt = $pdo->prepare("
                SELECT id, payment_intent_id, status
                FROM transactions
                WHERE transaction_reference = ? AND user_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$transactionRef, $userId]);
            $tx = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($tx && !empty($tx['payment_intent_id'])) {
                $paymentIntentId = (string)$tx['payment_intent_id'];
                $paymentIntent = $payMongoService->getPaymentIntent($paymentIntentId);

                if ($paymentIntent && isset($paymentIntent['attributes']['status'])) {
                    $paymentStatus = (string)$paymentIntent['attributes']['status'];

                    // If it already succeeded, treat cancel as a success from the user's POV.
                    if ($paymentStatus === 'succeeded') {
                        $transactionId = (int)$tx['id'];
                        $result = $payMongoService->processSuccessfulPayment($paymentIntentId, $userId, $transactionId);

                        unset($_SESSION['pending_transaction']);
                        if (!empty($result['success'])) {
                            $successMessage = urlencode(
                                "Payment successful! ₱" . number_format((float)$result['amount'], 2) .
                                " has been added to your account. New balance: ₱" . number_format((float)$result['new_balance'], 2)
                            );
                            header("Location: ../../index.php?page=buypoints&success=" . $successMessage);
                            exit;
                        }
                    }

                    // If it's still processing/needs confirmation, don't mark cancelled.
                    if ($paymentStatus === 'processing' || $paymentStatus === 'awaiting_payment_method' || $paymentStatus === 'awaiting_next_action') {
                        $stmt = $pdo->prepare("
                            UPDATE transactions
                            SET status = 'processing', updated_at = NOW()
                            WHERE id = ? AND user_id = ?
                        ");
                        $stmt->execute([(int)$tx['id'], $userId]);
                        unset($_SESSION['pending_transaction']);

                        $pendingMessage = urlencode("Your payment is still being processed. Please check again in a few minutes.");
                        header("Location: ../../index.php?page=buypoints&pending=" . $pendingMessage);
                        exit;
                    }
                } else {
                    // If provider status cannot be fetched yet (network lag / eventual consistency),
                    // keep the transaction in processing instead of prematurely cancelling.
                    $stmt = $pdo->prepare("
                        UPDATE transactions
                        SET status = 'processing', updated_at = NOW()
                        WHERE id = ? AND user_id = ?
                    ");
                    $stmt->execute([(int)$tx['id'], $userId]);
                    unset($_SESSION['pending_transaction']);

                    $pendingMessage = urlencode("Payment status is still being confirmed. Please check again in a few minutes.");
                    header("Location: ../../index.php?page=buypoints&pending=" . $pendingMessage);
                    exit;
                }
            }

            // Otherwise: mark as cancelled (no charges made).
            $stmt = $pdo->prepare("
                UPDATE transactions
                SET status = 'cancelled', updated_at = NOW()
                WHERE transaction_reference = ? AND user_id = ?
            ");
            $stmt->execute([$transactionRef, $userId]);

            logPayMongoTransaction('Payment Cancelled', [
                'user_id' => $userId,
                'transaction_reference' => $transactionRef,
                'affected_rows' => $stmt->rowCount()
            ]);

            // Clear pending transaction from session
            unset($_SESSION['pending_transaction']);
        }

        // Redirect with cancellation message
        $cancelMessage = urlencode("Payment was cancelled. No charges were made to your account.");
        header("Location: ../../index.php?page=buypoints&info=" . $cancelMessage);
        exit;

    } catch (Exception $e) {
        logPayMongoTransaction('Cancel Callback Error', [
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        throw $e;
    }
}