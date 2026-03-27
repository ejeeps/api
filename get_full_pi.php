<?php
/**
 * Get full payment intent ID
 */

require_once __DIR__ . '/config/connection.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get transaction 17
    $stmt = $pdo->prepare('SELECT id, payment_intent_id, status, amount FROM transactions WHERE id = 17');
    $stmt->execute();
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        echo "Transaction ID: {$transaction['id']}\n";
        echo "Full Payment Intent ID: {$transaction['payment_intent_id']}\n";
        echo "Status: {$transaction['status']}\n";
        echo "Amount: ₱{$transaction['amount']}\n";
    } else {
        echo "Transaction not found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
