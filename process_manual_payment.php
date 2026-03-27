<?php
/**
 * Manual Payment Processing Script
 * Use this to process payments that are stuck in "processing" status
 */

// Set server name for CLI execution - use localhost for local database
if (!isset($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = 'localhost';
}

require_once __DIR__ . '/config/connection.php';

// Force live mode for processing live payments
// This is needed because the payment was made in live mode
define('PAYMONGO_SECRET_KEY', 'sk_live_DxN7AUY9EzyaF8QpLfLQvHm6');
define('PAYMONGO_PUBLIC_KEY', 'pk_live_myEYtWqEx9Muy9phzNESXAUc');
define('PAYMONGO_MODE', 'live');

require_once __DIR__ . '/services/PayMongoService.php';

// Configuration - Change these values
// Use the payment intent ID from the successful payment
$paymentIntentId = 'pi_sgthte4MPZS2BaWrdftECUcU'; // From PayMongo dashboard (the successful payment)
$userId = 4; // From your database

echo "Processing manual payment...\n";
echo "Payment Intent ID: {$paymentIntentId}\n";
echo "User ID: {$userId}\n\n";

try {
    $payMongoService = new PayMongoService();
    
    // First, check the payment status from PayMongo
    echo "Checking payment status from PayMongo...\n";
    $paymentIntent = $payMongoService->getPaymentIntent($paymentIntentId);
    
    if (!$paymentIntent) {
        echo "ERROR: Could not retrieve payment intent from PayMongo.\n";
        exit(1);
    }
    
    $paymentStatus = $paymentIntent['attributes']['status'];
    $amount = convertToPesos($paymentIntent['attributes']['amount']);
    
    echo "PayMongo Status: {$paymentStatus}\n";
    echo "Amount: ₱" . number_format($amount, 2) . "\n\n";
    
    if ($paymentStatus !== 'succeeded') {
        echo "ERROR: Payment is not successful. Status: {$paymentStatus}\n";
        echo "Cannot process this payment yet.\n";
        exit(1);
    }
    
    // Find the transaction in database
    $database = new Database();
    $pdo = $database->getConnection();
    
    $stmt = $pdo->prepare("
        SELECT id, status, user_id 
        FROM transactions 
        WHERE payment_intent_id = ?
    ");
    $stmt->execute([$paymentIntentId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        echo "WARNING: Transaction not found with this payment_intent_id.\n";
        echo "Looking for pending transaction for user {$userId}...\n\n";
        
        // Find the most recent pending transaction for this user
        $stmt = $pdo->prepare("
            SELECT id, status, user_id, payment_intent_id, transaction_reference
            FROM transactions 
            WHERE user_id = ? AND status = 'processing'
            ORDER BY created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            echo "ERROR: No pending transaction found for user {$userId}.\n";
            echo "Cannot process payment without a transaction record.\n";
            exit(1);
        }
        
        echo "Found pending transaction:\n";
        echo "- Transaction ID: {$transaction['id']}\n";
        echo "- Old Payment Intent: {$transaction['payment_intent_id']}\n";
        echo "- Transaction Reference: {$transaction['transaction_reference']}\n";
        
        // Update the transaction with the correct payment intent ID
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET payment_intent_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$paymentIntentId, $transaction['id']]);
        echo "- Updated with correct payment intent ID\n\n";
        
        // Refresh transaction data
        $transaction['payment_intent_id'] = $paymentIntentId;
    } else {
        echo "Transaction found:\n";
        echo "- Transaction ID: {$transaction['id']}\n";
        echo "- Current Status: {$transaction['status']}\n";
        echo "- User ID: {$transaction['user_id']}\n\n";
        
        if ($transaction['status'] === 'completed') {
            echo "INFO: Transaction is already completed. No action needed.\n";
            exit(0);
        }
    }
    
    // Process the payment
    echo "Processing payment...\n";
    $result = $payMongoService->processSuccessfulPayment($paymentIntentId, $userId, $transaction['id']);
    
    if ($result['success']) {
        echo "\n✓ SUCCESS!\n";
        echo "Amount: ₱" . number_format($result['amount'], 2) . "\n";
        echo "New Balance: ₱" . number_format($result['new_balance'], 2) . "\n";
        echo "Transaction Reference: {$result['transaction_reference']}\n";
        
        if (isset($result['already_processed']) && $result['already_processed']) {
            echo "(Payment was already processed)\n";
        }
    } else {
        echo "\n✗ FAILED!\n";
        echo "Error: {$result['error']}\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
