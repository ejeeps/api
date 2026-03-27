<?php
/**
 * Cancel stuck transaction script
 */

if (!isset($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = 'localhost';
}

require_once __DIR__ . '/config/connection.php';

$database = new Database();
$pdo = $database->getConnection();

$paymentIntentId = 'pi_FBGpEeiLsJkoUCqyyh65qzTh';

$stmt = $pdo->prepare("
    UPDATE transactions 
    SET status = 'cancelled', 
        updated_at = NOW(), 
        description = CONCAT(description, ' - Abandoned by user, new payment created')
    WHERE payment_intent_id = ? 
    AND status = 'processing'
");

$stmt->execute([$paymentIntentId]);

echo "Updated " . $stmt->rowCount() . " transaction(s)\n";

// Show the transaction that was updated
$stmt = $pdo->prepare("SELECT id, status, transaction_reference FROM transactions WHERE payment_intent_id = ?");
$stmt->execute([$paymentIntentId]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if ($transaction) {
    echo "Transaction ID: {$transaction['id']}\n";
    echo "Status: {$transaction['status']}\n";
    echo "Reference: {$transaction['transaction_reference']}\n";
}
