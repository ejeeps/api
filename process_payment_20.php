<?php
/**
 * Process payment manually - finds transaction by payment_intent_id
 */

require_once __DIR__ . '/config/connection.php';
require_once __DIR__ . '/services/PayMongoService.php';

$paymentIntentId = 'pi_BwJdMJGGP9svThh4c67p1j3o';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Processing payment: {$paymentIntentId}\n\n";
    
    // Check PayMongo
    $payMongoService = new PayMongoService();
    $paymentIntent = $payMongoService->getPaymentIntent($paymentIntentId);
    
    if (!$paymentIntent) {
        echo "ERROR: Could not retrieve payment from PayMongo\n";
        exit;
    }
    
    $status = $paymentIntent['attributes']['status'];
    $amount = convertToPesos($paymentIntent['attributes']['amount']);
    
    echo "PayMongo Status: {$status}\n";
    echo "Amount: ₱" . number_format($amount, 2) . "\n";
    
    if ($status !== 'succeeded') {
        echo "ERROR: Payment not successful. Status: {$status}\n";
        exit;
    }
    
    // Find transaction by payment_intent_id in database
    $stmt = $pdo->prepare('SELECT id, status, user_id, card_id, amount FROM transactions WHERE payment_intent_id = ?');
    $stmt->execute([$paymentIntentId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        echo "ERROR: Transaction not found in database for this payment intent\n";
        exit;
    }
    
    $userId = $transaction['user_id'];
    $cardId = $transaction['card_id'];
    $dbAmount = $transaction['amount'];
    
    echo "Transaction ID: {$transaction['id']}\n";
    echo "User ID: {$userId}\n";
    echo "Card ID: {$cardId}\n";
    echo "Current Status: {$transaction['status']}\n";
    
    if ($transaction['status'] === 'completed') {
        echo "\nTransaction already completed!\n";
        exit;
    }
    
    // Get current balance
    $stmt = $pdo->prepare('SELECT balance FROM cards WHERE id = ?');
    $stmt->execute([$cardId]);
    $oldBalance = $stmt->fetchColumn();
    
    // Update transaction
    $stmt = $pdo->prepare('
        UPDATE transactions 
        SET status = "completed", 
            processed_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([$transaction['id']]);
    
    // Update card balance
    $stmt = $pdo->prepare('UPDATE cards SET balance = balance + ? WHERE id = ?');
    $stmt->execute([$amount, $cardId]);
    
    // Get new balance
    $stmt = $pdo->prepare('SELECT balance FROM cards WHERE id = ?');
    $stmt->execute([$cardId]);
    $newBalance = $stmt->fetchColumn();
    
    echo "\n✅ SUCCESS!\n";
    echo "Transaction ID: {$transaction['id']} marked as completed\n";
    echo "Old Balance: ₱" . number_format($oldBalance, 2) . "\n";
    echo "Amount Added: ₱" . number_format($amount, 2) . "\n";
    echo "New Balance: ₱" . number_format($newBalance, 2) . "\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
