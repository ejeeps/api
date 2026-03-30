<?php
/**
 * Database Connection Test & Transaction List
 */

require_once __DIR__ . '/config/connection.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "✅ Database Connection Successful!\n\n";
    
    // Get ALL transactions
    $stmt = $pdo->query('SELECT id, status, payment_intent_id, amount, user_id, card_id, created_at FROM transactions ORDER BY created_at DESC LIMIT 10');
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "All Recent Transactions:\n";
    echo str_repeat('-', 100) . "\n";
    foreach ($transactions as $t) {
        echo "ID: {$t['id']} | User: {$t['user_id']} | Card: {$t['card_id']} | Status: {$t['status']} | Amount: {$t['amount']} | PI: {$t['payment_intent_id']} | Date: {$t['created_at']}\n";
    }
    
    echo "\n" . str_repeat('-', 100) . "\n";
    
    // Check for the specific payment intent
    $searchPI = 'pi_BwJdMJGGP9svThh4c67p1j3o';
    echo "\nSearching for PI: {$searchPI}\n";
    $stmt = $pdo->prepare('SELECT * FROM transactions WHERE payment_intent_id LIKE ?');
    $stmt->execute(['%' . substr($searchPI, 0, 20) . '%']);
    $found = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($found) {
        echo "Found matching transactions:\n";
        print_r($found);
    } else {
        echo "No transaction found with this payment intent\n";
    }
    
    // Show user 4's card balance
    $stmt = $pdo->query('
        SELECT c.id, c.balance, p.user_id 
        FROM cards c 
        JOIN card_assign_passengers cap ON c.id = cap.card_id 
        JOIN passengers p ON cap.passenger_id = p.id 
        WHERE p.user_id = 4 AND cap.assignment_status = "active"
    ');
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nUser 4 Card Info:\n";
    if ($card) {
        echo "Card ID: {$card['id']}, Balance: ₱{$card['balance']}\n";
    } else {
        echo "No active card found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
