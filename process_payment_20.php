<?php
/**
 * Process payment manually - pi_wwFB722KeBcyEW5cPh3zJKEu
 */

require_once __DIR__ . '/config/connection.php';
require_once __DIR__ . '/services/PayMongoService.php';

$paymentIntentId = 'pi_wwFB722KeBcyEW5cPh3zJKEu';
$amount = 1.00;
$userId = 4;
$cardId = 3;

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Processing payment: {$paymentIntentId}\n";
    echo "Amount: ₱" . number_format($amount, 2) . "\n\n";
    
    // Check if transaction exists
    $stmt = $pdo->prepare('SELECT id, status FROM transactions WHERE payment_intent_id = ?');
    $stmt->execute([$paymentIntentId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        echo "Found transaction ID: {$transaction['id']}, Status: {$transaction['status']}\n";
        
        if ($transaction['status'] === 'completed') {
            echo "\nTransaction already completed!\n";
            exit;
        }
        
        // Update existing transaction
        $stmt = $pdo->prepare('
            UPDATE transactions 
            SET status = "completed", card_id = ?, processed_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([$cardId, $transaction['id']]);
        echo "Updated transaction to completed\n";
    } else {
        // Create new transaction
        $transactionRef = 'TXN_' . $userId . '_' . time() . '_' . rand(1000, 9999);
        
        $stmt = $pdo->prepare('
            INSERT INTO transactions (
                user_id, card_id, transaction_reference, payment_intent_id,
                amount, transaction_type, status, payment_method, 
                description, processed_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, "top_up", "completed", "paymongo", 
                "Points top-up via PayMongo - qrph (manual)", NOW(), NOW(), NOW())
        ');
        $stmt->execute([$userId, $cardId, $transactionRef, $paymentIntentId, $amount]);
        
        echo "Created new transaction ID: " . $pdo->lastInsertId() . "\n";
    }
    
    // Update card balance
    $stmt = $pdo->prepare('SELECT balance FROM cards WHERE id = ?');
    $stmt->execute([$cardId]);
    $oldBalance = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare('UPDATE cards SET balance = balance + ? WHERE id = ?');
    $stmt->execute([$amount, $cardId]);
    
    $stmt = $pdo->prepare('SELECT balance FROM cards WHERE id = ?');
    $stmt->execute([$cardId]);
    $newBalance = $stmt->fetchColumn();
    
    echo "\n✅ SUCCESS!\n";
    echo "Old Balance: ₱" . number_format($oldBalance, 2) . "\n";
    echo "Amount Added: ₱" . number_format($amount, 2) . "\n";
    echo "New Balance: ₱" . number_format($newBalance, 2) . "\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
