<?php
/**
 * Fix all stuck transactions and add the successful ₱1.00 payment
 */

require_once __DIR__ . '/config/connection.php';
require_once __DIR__ . '/services/PayMongoService.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== FIXING TRANSACTIONS ===\n\n";
    
    // Step 1: Cancel stuck transactions (ID 17 and 18)
    echo "Step 1: Cancelling stuck transactions...\n";
    
    $stmt = $pdo->prepare('
        UPDATE transactions 
        SET status = "cancelled", 
            updated_at = NOW(),
            description = CONCAT(COALESCE(description, ""), " - Cancelled: payment not completed")
        WHERE id IN (17, 18) AND status = "processing"
    ');
    $stmt->execute();
    echo "Cancelled {$stmt->rowCount()} stuck transactions\n\n";
    
    // Step 2: Create transaction for the successful payment
    echo "Step 2: Creating transaction for successful ₱1.00 payment...\n";
    
    $paymentIntentId = 'pi_BwJdMJGGP9svThh4c67p1j3o';
    $userId = 4;
    $cardId = 3;
    $amount = 1.00;
    
    // Check if already exists
    $stmt = $pdo->prepare('SELECT id FROM transactions WHERE payment_intent_id = ?');
    $stmt->execute([$paymentIntentId]);
    if ($stmt->fetch()) {
        echo "Transaction already exists for this payment intent\n";
    } else {
        // Generate transaction reference
        $transactionRef = 'TXN_' . $userId . '_' . time() . '_' . rand(1000, 9999);
        
        // Create transaction
        $stmt = $pdo->prepare('
            INSERT INTO transactions (
                user_id, card_id, transaction_reference, payment_intent_id,
                amount, transaction_type, status, payment_method, 
                description, processed_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, "top_up", "completed", "paymongo", 
                "Points top-up via PayMongo - qrph (manual reconciliation)", NOW(), NOW(), NOW())
        ');
        $stmt->execute([$userId, $cardId, $transactionRef, $paymentIntentId, $amount]);
        
        $newId = $pdo->lastInsertId();
        echo "Created transaction ID: {$newId}\n";
        echo "Reference: {$transactionRef}\n";
    }
    
    // Step 3: Update card balance
    echo "\nStep 3: Updating card balance...\n";
    
    $stmt = $pdo->prepare('SELECT balance FROM cards WHERE id = ?');
    $stmt->execute([$cardId]);
    $oldBalance = $stmt->fetchColumn();
    echo "Old Balance: ₱" . number_format($oldBalance, 2) . "\n";
    
    $stmt = $pdo->prepare('UPDATE cards SET balance = balance + ? WHERE id = ?');
    $stmt->execute([$amount, $cardId]);
    
    $stmt = $pdo->prepare('SELECT balance FROM cards WHERE id = ?');
    $stmt->execute([$cardId]);
    $newBalance = $stmt->fetchColumn();
    echo "Amount Added: ₱" . number_format($amount, 2) . "\n";
    echo "New Balance: ₱" . number_format($newBalance, 2) . "\n";
    
    echo "\n✅ ALL DONE!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
