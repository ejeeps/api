<?php
/**
 * PayMongo Webhook Handler
 * 
 * This controller handles PayMongo webhook events to process payments
 * and update user balances when payments are successful.
 * 
 * IMPORTANT: Per PayMongo best practices, we respond IMMEDIATELY with HTTP 200
 * and process the webhook in the background.
 */

require_once __DIR__ . '/../../config/paymongo.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../services/PayMongoService.php';

header('Content-Type: application/json; charset=utf-8');

// Browsers send GET; PayMongo sends POST. Some CDNs show a generic 500/404 for GET on API paths.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'endpoint' => 'PayMongoWebhookController',
        'hint' => 'This URL only accepts POST from PayMongo webhooks. Open in a browser is expected to show this message.',
    ]);
    exit;
}

// Get raw POST data BEFORE sending response
$rawPayload = file_get_contents('php://input');

// RESPOND IMMEDIATELY with HTTP 200 (per PayMongo best practices)
// This prevents timeouts and retries
http_response_code(200);
echo json_encode(['status' => 'received', 'message' => 'Webhook received']);

// Flush output to send response immediately
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    // For non-FastCGI environments
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
}

// Continue processing in background after response is sent
ignore_user_abort(true);
set_time_limit(60); // Allow up to 60 seconds for processing

// Now process the webhook
try {
    // Log webhook received
    logPayMongoTransaction('Webhook Received', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'raw_input_length' => strlen($rawPayload)
    ]);
    
    if (empty($rawPayload)) {
        logPayMongoTransaction('Webhook Error', ['error' => 'Empty payload received']);
        exit;
    }

    // Parse JSON payload
    $payload = json_decode($rawPayload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logPayMongoTransaction('Webhook Error', ['error' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }

    // Validate webhook signature (optional but recommended)
    $payMongoService = new PayMongoService();
    $headers = getallheaders();
    
    if (!empty(PAYMONGO_WEBHOOK_SECRET) && isset($headers['Paymongo-Signature'])) {
        if (!$payMongoService->validateWebhook($rawPayload, $headers['Paymongo-Signature'])) {
            logPayMongoTransaction('Webhook Signature Validation Failed', [
                'signature' => substr($headers['Paymongo-Signature'] ?? '', 0, 50) . '...'
            ]);
            exit;
        }
    }

    // Extract event data
    // PayMongo may send webhook payloads in slightly different shapes depending on the event.
    // Normalize to:
    // - $eventType: webhook event name if available, otherwise resource type
    // - $eventData: resource body containing `attributes`
    $eventType = $payload['data']['attributes']['type'] ?? $payload['type'] ?? '';
    $eventData = $payload['data']['attributes']['data']
        ?? $payload['data']['attributes']
        ?? $payload;

    logPayMongoTransaction('Processing Webhook Event', [
        'event_type' => $eventType,
        'event_id' => $payload['data']['id'] ?? $payload['id'] ?? 'unknown',
        'event_data_type' => $eventData['type'] ?? null
    ]);

    // Handle different event types
    switch ($eventType) {
        case 'payment_intent.succeeded':
            handlePaymentSucceeded($eventData, $payMongoService);
            break;

        case 'payment_intent.payment_failed':
            handlePaymentFailed($eventData, $payMongoService);
            break;

        case 'checkout_session.payment_paid':
        case 'checkout_session.payment.paid':
            handleCheckoutPaymentPaid($eventData, $payMongoService);
            break;

        case 'payment.paid':
            handlePaymentPaid($eventData, $payMongoService);
            break;

        // Fallbacks when webhook event type is only the resource type
        // (some webhook payloads provide `type: payment` and rely on attributes.status to identify `paid`)
        case 'payment':
            if (($eventData['attributes']['status'] ?? null) === 'paid') {
                handlePaymentPaid($eventData, $payMongoService);
            }
            break;

        case 'checkout_session':
            if (!empty($eventData['attributes']['paid_at'] ?? null)) {
                handleCheckoutPaymentPaid($eventData, $payMongoService);
            }
            break;

        case 'qrph.expired':
        case 'payment_method.expired':
            handlePaymentExpired($eventData, $payMongoService);
            break;

        default:
            logPayMongoTransaction('Unhandled Webhook Event', [
                'event_type' => $eventType
            ]);
            break;
    }

    logPayMongoTransaction('Webhook Processing Complete', [
        'event_type' => $eventType
    ]);

} catch (Throwable $e) {
    logPayMongoTransaction('Webhook Processing Error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    http_response_code(200);
    echo json_encode([
        'status' => 'received',
        'message' => 'Webhook error logged (check logs/paymongo.log)',
    ]);
    exit;
}

http_response_code(200);
echo json_encode(['status' => 'received', 'message' => 'Webhook processed']);

/**
 * Find the pending/processing transactions row for webhook completion.
 * PayMongo sometimes reports a different payment_intent_id than we stored; checkout_session_id
 * and amount help match the correct row.
 *
 * @return array{id: int, user_id: int, card_id: int|null}|null
 */
function resolvePendingTransactionForWebhook(PDO $pdo, ?string $paymentIntentId, ?string $checkoutSessionId, ?int $userId, ?float $amountPesos) {
    $parts = [];
    $params = [];
    if ($paymentIntentId !== null && $paymentIntentId !== '') {
        $parts[] = 'payment_intent_id = ?';
        $params[] = $paymentIntentId;
    }
    if ($checkoutSessionId !== null && $checkoutSessionId !== '') {
        $parts[] = 'checkout_session_id = ?';
        $params[] = $checkoutSessionId;
    }
    if (!empty($parts)) {
        $sql = 'SELECT id, user_id, card_id FROM transactions WHERE status IN (\'pending\', \'processing\') AND (' . implode(' OR ', $parts) . ') ORDER BY created_at DESC LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }
    if ($userId !== null && $amountPesos !== null && $amountPesos > 0) {
        $stmt = $pdo->prepare('SELECT id, user_id, card_id FROM transactions WHERE status IN (\'pending\', \'processing\') AND user_id = ? AND amount = ? ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$userId, $amountPesos]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }
    if ($amountPesos !== null && $amountPesos > 0) {
        $stmt = $pdo->prepare('SELECT id, user_id, card_id FROM transactions WHERE status IN (\'pending\', \'processing\') AND amount = ?');
        $stmt->execute([$amountPesos]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) === 1) {
            return $rows[0];
        }
    }
    return null;
}

/**
 * Handle successful payment intent
 */
function handlePaymentSucceeded($eventData, $payMongoService) {
    try {
        $paymentIntentId = $eventData['id'] ?? '';
        $metadata = $eventData['attributes']['metadata'] ?? [];
        $userId = $metadata['user_id'] ?? null;
        $transactionId = $metadata['transaction_id'] ?? null;

        if (!$paymentIntentId || !$userId) {
            throw new Exception('Missing payment intent ID or user ID');
        }

        logPayMongoTransaction('Processing Payment Success', [
            'payment_intent_id' => $paymentIntentId,
            'user_id' => $userId,
            'transaction_id_meta' => $transactionId
        ]);

        // Match DB row even when metadata omits transaction_id or intent id drifted
        if (!$transactionId) {
            $database = new Database();
            $pdo = $database->getConnection();
            $amountPesos = null;
            if (isset($eventData['attributes']['amount'])) {
                $amountPesos = convertToPesos($eventData['attributes']['amount']);
            }
            $resolved = resolvePendingTransactionForWebhook($pdo, $paymentIntentId, null, (int) $userId, $amountPesos);
            if ($resolved) {
                $transactionId = $resolved['id'];
                logPayMongoTransaction('Webhook Payment Success - Matched Existing Transaction', [
                    'payment_intent_id' => $paymentIntentId,
                    'user_id' => $userId,
                    'transaction_id' => $transactionId
                ]);
            } else {
                logPayMongoTransaction('Webhook Payment Success - No Matching Transaction Found', [
                    'payment_intent_id' => $paymentIntentId,
                    'user_id' => $userId
                ]);
            }
        }

        // Process the successful payment
        $result = $payMongoService->processSuccessfulPayment($paymentIntentId, $userId, $transactionId);

        if ($result['success']) {
            logPayMongoTransaction('Payment Processed Successfully via Webhook', [
                'user_id' => $userId,
                'amount' => $result['amount'],
                'new_balance' => $result['new_balance'],
                'transaction_reference' => $result['transaction_reference']
            ]);
        } else {
            logPayMongoTransaction('Payment Processing Failed', [
                'error' => $result['error'] ?? 'Failed to process payment',
                'payment_intent_id' => $paymentIntentId
            ]);
        }

    } catch (Exception $e) {
        logPayMongoTransaction('Payment Success Handling Error', [
            'error' => $e->getMessage(),
            'event_data' => $eventData
        ]);
        // Don't throw - let the main handler return 200
    }
}

/**
 * Handle failed payment intent
 */
function handlePaymentFailed($eventData, $payMongoService) {
    try {
        $paymentIntentId = $eventData['id'] ?? '';
        $metadata = $eventData['attributes']['metadata'] ?? [];
        $userId = $metadata['user_id'] ?? null;
        $transactionRef = $metadata['transaction_reference'] ?? null;

        logPayMongoTransaction('Processing Payment Failure', [
            'payment_intent_id' => $paymentIntentId,
            'user_id' => $userId,
            'transaction_reference' => $transactionRef
        ]);

        if ($paymentIntentId) {
            // Update transaction status to failed
            $database = new Database();
            $pdo = $database->getConnection();

            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'failed', updated_at = NOW() 
                WHERE payment_intent_id = ?
            ");
            $stmt->execute([$paymentIntentId]);

            logPayMongoTransaction('Transaction Marked as Failed', [
                'payment_intent_id' => $paymentIntentId,
                'affected_rows' => $stmt->rowCount()
            ]);
        }

    } catch (Exception $e) {
        logPayMongoTransaction('Payment Failure Handling Error', [
            'error' => $e->getMessage(),
            'event_data' => $eventData
        ]);
        // Don't throw - let the main handler return 200
    }
}

/**
 * Handle checkout session payment paid (alternative event)
 */
function handleCheckoutPaymentPaid($eventData, $payMongoService) {
    try {
        $attrs = $eventData['attributes'] ?? [];
        $piNested = $attrs['payment_intent'] ?? null;
        $paymentIntentId = '';
        if (is_array($piNested)) {
            $paymentIntentId = $piNested['id'] ?? $piNested['data']['id'] ?? '';
        }
        if ($paymentIntentId === '') {
            $paymentIntentId = $attrs['payment_intent_id'] ?? $eventData['payment_intent_id'] ?? '';
        }

        $checkoutSessionId = (($eventData['type'] ?? '') === 'checkout_session') ? ($eventData['id'] ?? null) : null;

        if (!$paymentIntentId) {
            logPayMongoTransaction('Checkout.Payment.Paid - No payment_intent_id found', [
                'event_data_keys' => array_keys($eventData),
                'attributes_keys' => isset($eventData['attributes']) ? array_keys($eventData['attributes']) : 'no attributes'
            ]);
            return;
        }

        logPayMongoTransaction('Checkout.Payment.Paid - Found payment_intent_id', [
            'payment_intent_id' => $paymentIntentId,
            'checkout_session_id' => $checkoutSessionId
        ]);

        // Get payment intent details to extract metadata
        $paymentIntent = $payMongoService->getPaymentIntent($paymentIntentId);

        if (!$paymentIntent) {
            logPayMongoTransaction('Checkout.Payment.Paid - Could not retrieve payment intent', [
                'payment_intent_id' => $paymentIntentId
            ]);
            return;
        }

        // Check for user_id in metadata first
        $metadata = $paymentIntent['attributes']['metadata'] ?? [];
        $userId = isset($metadata['user_id']) ? (int) $metadata['user_id'] : null;
        $transactionId = isset($metadata['transaction_id']) ? (int) $metadata['transaction_id'] : null;

        $database = new Database();
        $pdo = $database->getConnection();
        $amountPesos = convertToPesos($paymentIntent['attributes']['amount'] ?? 0);

        // Must resolve DB row when metadata is empty OR only user_id is set (missing transaction_id inserts a duplicate row otherwise)
        if (!$userId || !$transactionId) {
            logPayMongoTransaction('Checkout.Payment.Paid - Resolving transaction row', [
                'payment_intent_id' => $paymentIntentId,
                'checkout_session_id' => $checkoutSessionId,
                'has_user_in_meta' => (bool) $userId,
                'has_txn_in_meta' => (bool) $transactionId
            ]);
            $resolved = resolvePendingTransactionForWebhook($pdo, $paymentIntentId, $checkoutSessionId, $userId, $amountPesos);
            if ($resolved) {
                $userId = (int) $resolved['user_id'];
                $transactionId = (int) $resolved['id'];
                logPayMongoTransaction('Checkout.Payment.Paid - Resolved transaction', [
                    'transaction_id' => $transactionId,
                    'user_id' => $userId
                ]);
            }
        }

        if (!$userId || !$transactionId) {
            logPayMongoTransaction('Checkout.Payment.Paid - Transaction not found in database', [
                'payment_intent_id' => $paymentIntentId,
                'checkout_session_id' => $checkoutSessionId
            ]);
            return;
        }

        logPayMongoTransaction('Processing Checkout Payment Paid', [
            'payment_intent_id' => $paymentIntentId,
            'user_id' => $userId,
            'transaction_id' => $transactionId
        ]);

        // Process the successful payment
        $result = $payMongoService->processSuccessfulPayment($paymentIntentId, $userId, $transactionId);

        if (!$result['success']) {
            logPayMongoTransaction('Checkout.Payment.Paid - Processing failed', [
                'payment_intent_id' => $paymentIntentId,
                'user_id' => $userId,
                'error' => $result['error'] ?? 'Unknown error'
            ]);
        } else {
            logPayMongoTransaction('Checkout.Payment.Paid - Processing successful', [
                'payment_intent_id' => $paymentIntentId,
                'user_id' => $userId,
                'amount' => $result['amount'],
                'new_balance' => $result['new_balance']
            ]);
        }

    } catch (Exception $e) {
        logPayMongoTransaction('Checkout Payment Paid Handling Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'event_data' => $eventData
        ]);
        // Don't re-throw - we want to return 200 to PayMongo
    }
}

/**
 * Handle payment.paid event (alternative event for successful payments)
 */
function handlePaymentPaid($eventData, $payMongoService) {
    try {
        $attrs = $eventData['attributes'] ?? [];
        $paymentIntentId = $attrs['payment_intent_id'] ??
                          $eventData['payment_intent_id'] ??
                          $attrs['source']['payment_intent_id'] ??
                          '';

        $amountPesosFromEvent = isset($attrs['amount']) ? convertToPesos($attrs['amount']) : null;

        if (!$paymentIntentId) {
            logPayMongoTransaction('Payment.Paid - No payment_intent_id found', [
                'event_data_keys' => array_keys($eventData),
                'attributes_keys' => isset($eventData['attributes']) ? array_keys($eventData['attributes']) : 'no attributes'
            ]);
            return; // Don't throw exception, just log and return
        }

        logPayMongoTransaction('Payment.Paid - Found payment_intent_id', [
            'payment_intent_id' => $paymentIntentId
        ]);

        // Get payment intent details to extract metadata
        $paymentIntent = $payMongoService->getPaymentIntent($paymentIntentId);

        if (!$paymentIntent) {
            logPayMongoTransaction('Payment.Paid - Could not retrieve payment intent', [
                'payment_intent_id' => $paymentIntentId
            ]);
            return;
        }

        $metadata = $paymentIntent['attributes']['metadata'] ?? [];
        $userId = isset($metadata['user_id']) ? (int) $metadata['user_id'] : null;
        $transactionId = isset($metadata['transaction_id']) ? (int) $metadata['transaction_id'] : null;

        $database = new Database();
        $pdo = $database->getConnection();
        $amountPesos = $amountPesosFromEvent ?? convertToPesos($paymentIntent['attributes']['amount'] ?? 0);

        if (!$userId || !$transactionId) {
            logPayMongoTransaction('Payment.Paid - Resolving transaction row', [
                'payment_intent_id' => $paymentIntentId,
                'has_user_in_meta' => (bool) $userId,
                'has_txn_in_meta' => (bool) $transactionId
            ]);
            $resolved = resolvePendingTransactionForWebhook($pdo, $paymentIntentId, null, $userId, $amountPesos);
            if ($resolved) {
                $userId = (int) $resolved['user_id'];
                $transactionId = (int) $resolved['id'];
                logPayMongoTransaction('Payment.Paid - Resolved transaction', [
                    'transaction_id' => $transactionId,
                    'user_id' => $userId
                ]);
            }
        }

        if (!$userId || !$transactionId) {
            logPayMongoTransaction('Payment.Paid - Transaction not found in database', [
                'payment_intent_id' => $paymentIntentId
            ]);
            return;
        }

        logPayMongoTransaction('Processing Payment.Paid Event', [
            'payment_intent_id' => $paymentIntentId,
            'user_id' => $userId,
            'transaction_id' => $transactionId
        ]);

        // Process the successful payment
        $result = $payMongoService->processSuccessfulPayment($paymentIntentId, $userId, $transactionId);

        if (!$result['success']) {
            logPayMongoTransaction('Payment.Paid - Processing failed', [
                'payment_intent_id' => $paymentIntentId,
                'user_id' => $userId,
                'error' => $result['error'] ?? 'Unknown error'
            ]);
            // Don't throw exception, just log the error
        } else {
            logPayMongoTransaction('Payment.Paid - Processing successful', [
                'payment_intent_id' => $paymentIntentId,
                'user_id' => $userId,
                'amount' => $result['amount'],
                'new_balance' => $result['new_balance']
            ]);
        }

    } catch (Exception $e) {
        logPayMongoTransaction('Payment Paid Handling Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'event_data' => $eventData
        ]);
        // Don't re-throw - we want to return 200 to PayMongo even if processing fails
    }
}

/**
 * Handle expired payment methods (QR PH, etc.)
 */
function handlePaymentExpired($eventData, $payMongoService) {
    try {
        $paymentIntentId = $eventData['attributes']['payment_intent_id'] ?? '';
        $sourceId = $eventData['attributes']['source']['id'] ?? '';

        logPayMongoTransaction('Payment Method Expired', [
            'payment_intent_id' => $paymentIntentId,
            'source_id' => $sourceId,
            'event_type' => $eventData['type'] ?? 'unknown'
        ]);

        if ($paymentIntentId) {
            // Update transaction status to failed/cancelled
            $database = new Database();
            $pdo = $database->getConnection();

            $stmt = $pdo->prepare("
                UPDATE transactions
                SET status = 'cancelled', updated_at = NOW(),
                    description = CONCAT(description, ' - Payment expired')
                WHERE payment_intent_id = ?
            ");
            $stmt->execute([$paymentIntentId]);

            logPayMongoTransaction('Transaction Marked as Expired', [
                'payment_intent_id' => $paymentIntentId,
                'affected_rows' => $stmt->rowCount()
            ]);
        }

    } catch (Exception $e) {
        logPayMongoTransaction('Payment Expired Handling Error', [
            'error' => $e->getMessage(),
            'event_data' => $eventData
        ]);
        // Don't throw - let the main handler return 200
    }
}

/**
 * Get all headers (polyfill for older PHP versions)
 */
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}