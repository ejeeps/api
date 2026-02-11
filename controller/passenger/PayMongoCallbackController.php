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
        // Get checkout session ID from URL parameters
        $checkoutSessionId = $_GET['checkout_session_id'] ?? null;
        
        if (!$checkoutSessionId) {
            // If no checkout session ID, check for pending transaction in session
            $transactionRef = $_SESSION['pending_transaction'] ?? null;
            
            if ($transactionRef) {
                // Find the transaction by reference
                $database = new Database();
                $pdo = $database->getConnection();
                
                $stmt = $pdo->prepare("
                    SELECT payment_intent_id, checkout_session_id, status, amount 
                    FROM transactions 
                    WHERE transaction_reference = ? AND user_id = ?
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$transactionRef, $userId]);
                $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($transaction) {
                    $checkoutSessionId = $transaction['checkout_session_id'];
                }
            }
        }

        if (!$checkoutSessionId) {
            throw new Exception('No checkout session found. Please try again.');
        }

        // Get checkout session details
        $checkoutSession = $payMongoService->getCheckoutSession($checkoutSessionId);
        
        if (!$checkoutSession) {
            throw new Exception('Failed to retrieve checkout session details.');
        }

        $paymentIntentId = $checkoutSession['attributes']['payment_intent_id'];
        
        // Get payment intent details
        $paymentIntent = $payMongoService->getPaymentIntent($paymentIntentId);
        
        if (!$paymentIntent) {
            throw new Exception('Failed to retrieve payment details.');
        }

        $paymentStatus = $paymentIntent['attributes']['status'];
        $amount = convertToPesos($paymentIntent['attributes']['amount']);

        logPayMongoTransaction('Success Callback Processing', [
            'user_id' => $userId,
            'payment_intent_id' => $paymentIntentId,
            'payment_status' => $paymentStatus,
            'amount' => $amount
        ]);

        if ($paymentStatus === 'succeeded') {
            // Payment was successful, process it
            $result = $payMongoService->processSuccessfulPayment($paymentIntentId, $userId);
            
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
            
        } elseif ($paymentStatus === 'processing') {
            // Payment is still processing, show pending message
            $pendingMessage = urlencode("Your payment is being processed. You will receive confirmation shortly.");
            header("Location: ../../index.php?page=buypoints&pending=" . $pendingMessage);
            exit;
            
        } else {
            // Payment failed or other status
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
            // Update transaction status to cancelled
            $database = new Database();
            $pdo = $database->getConnection();
            
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