<?php
/**
 * Process the ₱1.00 payment manually
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
    
    // Get metadata
    $metadata = $paymentIntent['attributes']['metadata'] ?? [];
    $userId = $metadata['user_id'] ?? null;
    $transactionRef = $metadata['transaction_reference'] ?? null;
    
    echo "User ID: {$userId}\n";
    echo "Transaction Ref: {$transactionRef}\n";
    
    if (!$userId) {
        echo "ERROR: No user_id in metadata\n";
        exit;
    }
    
    // Find transaction
    $stmt = $pdo->prepare('SELECT id, status FROM transactions WHERE payment_intent_id = ?');
    $stmt->execute([$paymentIntentId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        echo "ERROR: Transaction not found in database\n";
        exit;
    }
    
    echo "Transaction ID: {$transaction['id']}, Current Status: {$transaction['status']}\n";
    
    if ($transaction['status'] === 'completed') {
        echo "Transaction already completed!\n";
        exit;
    }
    
    // Get user's card
    $stmt = $pdo->prepare('
        SELECT c.id as card_id, c.balance 
        FROM passengers p 
        JOIN card_assign_passengers cap ON p.id = cap.passenger_id 
        JOIN cards c ON cap.card_id = c.id 
        WHERE p.user_id = ? AND cap.assignment_status = "active" AND c.status = "active"
        LIMIT 1
    ');
    $stmt->execute([$userId]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$card) {
        echo "ERROR: No active card found for user {$userId}\n";
        exit;
    }
    
    $cardId = $card['card_id'];
    $oldBalance = $card['balance'];
    
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
