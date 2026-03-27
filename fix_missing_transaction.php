<?php
/**
 * Fix Missing Transaction
 * Creates transaction record for successful payment and cancels old stuck one
 */

require_once __DIR__ . '/config/connection.php';
require_once __DIR__ . '/services/PayMongoService.php';

// The successful payment from PayMongo
$paymentIntentId = 'pi_sgthte4MPZS2BaWrdftECUcU';
$userId = 4;

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "=== FIXING MISSING TRANSACTION ===\n\n";
    
    // Step 1: Check if successful payment transaction already exists
    echo "Step 1: Checking for existing transaction for successful payment...\n";
    $stmt = $pdo->prepare('SELECT id, status FROM transactions WHERE payment_intent_id = ?');
    $stmt->execute([$paymentIntentId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        echo "Transaction already exists: ID {$existing['id']}, Status: {$existing['status']}\n";
    } else {
        echo "Transaction not found. Checking PayMongo...\n";
        
        // Get payment details from PayMongo
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
            echo "ERROR: Payment not successful. Cannot create transaction.\n";
            exit;
        }
        
        // Get user's card
        $stmt = $pdo->prepare('
            SELECT c.id as card_id 
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
        
        // Create transaction record
        $transactionRef = generateTransactionReference($userId);
        
        $stmt = $pdo->prepare('
            INSERT INTO transactions (
                user_id, card_id, transaction_reference, payment_intent_id,
                amount, transaction_type, status, payment_method, 
                description, processed_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, "top_up", "completed", "paymongo", 
                "Points top-up via PayMongo - qrph (manual reconciliation)", NOW(), NOW(), NOW())
        ');
        
        $stmt->execute([
            $userId,
            $cardId,
            $transactionRef,
            $paymentIntentId,
            $amount
        ]);
        
        $newTransactionId = $pdo->lastInsertId();
        
        // Update card balance
        $stmt = $pdo->prepare('UPDATE cards SET balance = balance + ? WHERE id = ?');
        $stmt->execute([$amount, $cardId]);
        
        // Get new balance
        $stmt = $pdo->prepare('SELECT balance FROM cards WHERE id = ?');
        $stmt->execute([$cardId]);
        $newBalance = $stmt->fetchColumn();
        
        echo "\n✅ SUCCESS! Transaction created.\n";
        echo "Transaction ID: {$newTransactionId}\n";
        echo "Reference: {$transactionRef}\n";
        echo "Amount: ₱" . number_format($amount, 2) . "\n";
        echo "New Balance: ₱" . number_format($newBalance, 2) . "\n";
    }
    
    // Step 2: Cancel the old stuck transaction
    echo "\n=== CANCELLING OLD STUCK TRANSACTION ===\n\n";
    
    $oldPaymentIntentId = 'pi_FBGpEeiLsJkoUCqyyh65qzTh';
    
    $stmt = $pdo->prepare('
        UPDATE transactions 
        SET status = "cancelled", 
            updated_at = NOW(),
            description = CONCAT(description, " - Cancelled: user created new payment")
        WHERE payment_intent_id = ? AND status = "processing"
    ');
    $stmt->execute([$oldPaymentIntentId]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Cancelled old stuck transaction (PI: {$oldPaymentIntentId})\n";
    } else {
        echo "Old transaction not found or already cancelled.\n";
    }
    
    echo "\n=== DONE ===\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
