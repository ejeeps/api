<?php
if (!isset($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = 'localhost';
}

require_once __DIR__ . '/config/connection.php';

$database = new Database();
$pdo = $database->getConnection();

// Check for the specific payment intent
echo "Looking for pi_FBGpEeiLsJkoUCqyyh65qzTh:\n";
$stmt = $pdo->prepare('SELECT id, status, payment_intent_id, amount, transaction_reference, user_id FROM transactions WHERE payment_intent_id = ?');
$stmt->execute(['pi_FBGpEeiLsJkoUCqyyh65qzTh']);
$t = $stmt->fetch(PDO::FETCH_ASSOC);
if ($t) {
    echo "Found: ID={$t['id']}, Status={$t['status']}, User={$t['user_id']}\n";
    print_r($t);
} else {
    echo "Not found in database\n";
}

echo "\n\nAll processing transactions:\n";
$stmt = $pdo->query('SELECT id, status, payment_intent_id, amount, transaction_reference, user_id FROM transactions WHERE status = "processing" ORDER BY created_at DESC');
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($transactions as $t) {
    echo "ID: {$t['id']}, User: {$t['user_id']}, Status: {$t['status']}, Amount: {$t['amount']}, PI: {$t['payment_intent_id']}\n";
}
