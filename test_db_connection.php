<?php
/**
 * Database Connection Test
 * Upload this to your server and access via browser to test DB connection
 */

require_once __DIR__ . '/config/connection.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "✅ Database Connection Successful!\n\n";
    
    // Test query - get recent transactions
    $stmt = $pdo->query('SELECT id, status, payment_intent_id, amount, user_id, created_at FROM transactions ORDER BY created_at DESC LIMIT 5');
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Recent Transactions:\n";
    echo str_repeat('-', 80) . "\n";
    foreach ($transactions as $t) {
        echo "ID: {$t['id']} | User: {$t['user_id']} | Status: {$t['status']} | Amount: {$t['amount']} | PI: " . substr($t['payment_intent_id'], 0, 20) . "... | Date: {$t['created_at']}\n";
    }
    
    echo "\n" . str_repeat('-', 80) . "\n";
    
    // Check for stuck transactions
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM transactions WHERE status = "processing"');
    $stuck = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "\nStuck transactions (processing): {$stuck['count']}\n";
    
    // Check for completed transactions today
    $stmt = $pdo->query('SELECT COUNT(*) as count, SUM(amount) as total FROM transactions WHERE status = "completed" AND DATE(created_at) = CURDATE()');
    $today = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Completed transactions today: {$today['count']} (Total: ₱" . number_format($today['total'] ?? 0, 2) . ")\n";
    
} catch (Exception $e) {
    echo "❌ Database Connection Failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
}
