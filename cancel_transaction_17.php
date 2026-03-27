<?php
/**
 * Cancel abandoned transaction (ID 17)
 */

require_once __DIR__ . '/config/connection.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Cancelling abandoned transaction ID 17...\n\n";
    
    // Update transaction to cancelled
    $stmt = $pdo->prepare('
        UPDATE transactions 
        SET status = "cancelled", 
            updated_at = NOW(),
            description = CONCAT(description, " - Cancelled: QR expired without payment")
        WHERE id = 17 AND status = "processing"
    ');
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Transaction ID 17 cancelled successfully\n";
        echo "Reason: QR code expired without payment\n";
    } else {
        echo "Transaction not found or already cancelled\n";
    }
    
    // Show current status
    $stmt = $pdo->prepare('SELECT id, status, amount FROM transactions WHERE id = 17');
    $stmt->execute();
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        echo "\nCurrent Status:\n";
        echo "ID: {$transaction['id']}\n";
        echo "Status: {$transaction['status']}\n";
        echo "Amount: ₱{$transaction['amount']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
