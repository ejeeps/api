<?php
/**
 * Check Payment Status API
 * 
 * This endpoint allows users to check the status of their pending payments
 * and updates the balance if the payment was successful.
 */

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../services/PayMongoService.php';

// Set content type
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];

// Get pending transaction from session or database
$transactionRef = $_SESSION['pending_transaction'] ?? null;

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Find the most recent pending/processing transaction for this user
    $stmt = $pdo->prepare("
        SELECT id, transaction_reference, payment_intent_id, checkout_session_id, 
               status, amount, created_at
        FROM transactions 
        WHERE user_id = ? AND status IN ('pending', 'processing')
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        echo json_encode([
            'success' => true,
            'has_pending' => false,
            'message' => 'No pending transactions found'
        ]);
        exit;
    }
    
    // If we have a payment intent ID, check with PayMongo
    if ($transaction['payment_intent_id']) {
        $payMongoService = new PayMongoService();
        $paymentIntent = $payMongoService->getPaymentIntent($transaction['payment_intent_id']);
        
        if ($paymentIntent) {
            $paymentStatus = $paymentIntent['attributes']['status'];
            
            logPayMongoTransaction('Payment Status Check', [
                'user_id' => $userId,
                'transaction_id' => $transaction['id'],
                'payment_intent_id' => $transaction['payment_intent_id'],
                'payment_status' => $paymentStatus
            ]);
            
            if ($paymentStatus === 'succeeded') {
                // Payment successful - process it
                $result = $payMongoService->processSuccessfulPayment(
                    $transaction['payment_intent_id'], 
                    $userId, 
                    $transaction['id']
                );
                
                if ($result['success']) {
                    // Clear pending transaction from session
                    unset($_SESSION['pending_transaction']);
                    
                    echo json_encode([
                        'success' => true,
                        'has_pending' => false,
                        'status' => 'completed',
                        'message' => 'Payment successful! Your balance has been updated.',
                        'amount' => $result['amount'],
                        'new_balance' => $result['new_balance']
                    ]);
                    exit;
                }
            } elseif ($paymentStatus === 'processing' || $paymentStatus === 'awaiting_payment_method') {
                // Still processing
                echo json_encode([
                    'success' => true,
                    'has_pending' => true,
                    'status' => 'processing',
                    'message' => 'Payment is still being processed. Please check again in a few moments.',
                    'transaction_reference' => $transaction['transaction_reference'],
                    'amount' => $transaction['amount']
                ]);
                exit;
            } else {
                // Payment failed or other status
                $stmt = $pdo->prepare("
                    UPDATE transactions 
                    SET status = 'failed', updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$transaction['id']]);
                
                unset($_SESSION['pending_transaction']);
                
                echo json_encode([
                    'success' => true,
                    'has_pending' => false,
                    'status' => 'failed',
                    'message' => 'Payment was not successful. Status: ' . $paymentStatus
                ]);
                exit;
            }
        } else {
            // Could not retrieve payment intent from PayMongo
            echo json_encode([
                'success' => true,
                'has_pending' => true,
                'status' => 'unknown',
                'message' => 'Unable to check payment status at this time. Please try again later.',
                'transaction_reference' => $transaction['transaction_reference']
            ]);
            exit;
        }
    } else {
        // No payment intent ID yet
        echo json_encode([
            'success' => true,
            'has_pending' => true,
            'status' => 'pending',
            'message' => 'Payment is being prepared. Please complete the checkout process.',
            'transaction_reference' => $transaction['transaction_reference']
        ]);
        exit;
    }
    
} catch (Exception $e) {
    logPayMongoTransaction('Check Payment Status Error', [
        'user_id' => $userId,
        'error' => $e->getMessage()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while checking payment status'
    ]);
}
