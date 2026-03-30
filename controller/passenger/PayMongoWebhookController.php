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

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../services/PayMongoService.php';

// Set headers for webhook response
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
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

} catch (Exception $e) {
    logPayMongoTransaction('Webhook Processing Error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
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

        // If transaction_id is not in metadata, try to find the existing pending/processing transaction
        if (!$transactionId) {
            $database = new Database();
            $pdo = $database->getConnection();

            $stmt = $pdo->prepare("
                SELECT id 
                FROM transactions
                WHERE payment_intent_id = ? AND user_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$paymentIntentId, $userId]);
            $tx = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($tx) {
                $transactionId = $tx['id'];
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
        // Extract payment intent from checkout session - try multiple paths
        $paymentIntentId = $eventData['attributes']['payment_intent']['id'] ??
                          $eventData['attributes']['payment_intent_id'] ??
                          $eventData['payment_intent_id'] ??
                          '';

        if (!$paymentIntentId) {
            logPayMongoTransaction('Checkout.Payment.Paid - No payment_intent_id found', [
                'event_data_keys' => array_keys($eventData),
                'attributes_keys' => isset($eventData['attributes']) ? array_keys($eventData['attributes']) : 'no attributes'
            ]);
            return;
        }

        logPayMongoTransaction('Checkout.Payment.Paid - Found payment_intent_id', [
            'payment_intent_id' => $paymentIntentId
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
        $userId = $metadata['user_id'] ?? null;
        $transactionId = $metadata['transaction_id'] ?? null;

        // If no user_id in metadata, look up from database by payment_intent_id
        if (!$userId) {
            logPayMongoTransaction('Checkout.Payment.Paid - No user_id in metadata, looking up from database', [
                'payment_intent_id' => $paymentIntentId
            ]);
            
            $database = new Database();
            $pdo = $database->getConnection();
            
            $stmt = $pdo->prepare('SELECT id, user_id, card_id FROM transactions WHERE payment_intent_id = ? LIMIT 1');
            $stmt->execute([$paymentIntentId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($transaction) {
                $userId = $transaction['user_id'];
                $transactionId = $transaction['id'];
                logPayMongoTransaction('Checkout.Payment.Paid - Found transaction in database', [
                    'payment_intent_id' => $paymentIntentId,
                    'user_id' => $userId,
                    'transaction_id' => $transactionId
                ]);
            } else {
                // Fallback: sometimes matching by payment_intent_id fails or metadata is missing
                // Use checkout_session_id (resource id) if present.
                $checkoutSessionId = $eventData['id'] ?? null;
                if ($checkoutSessionId) {
                    logPayMongoTransaction('Checkout.Payment.Paid - Trying lookup by checkout_session_id', [
                        'checkout_session_id' => $checkoutSessionId
                    ]);

                    $stmt = $pdo->prepare('SELECT id, user_id, card_id FROM transactions WHERE checkout_session_id = ? LIMIT 1');
                    $stmt->execute([$checkoutSessionId]);
                    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($transaction) {
                        $userId = $transaction['user_id'];
                        $transactionId = $transaction['id'];
                        logPayMongoTransaction('Checkout.Payment.Paid - Found transaction via checkout_session_id', [
                            'checkout_session_id' => $checkoutSessionId,
                            'user_id' => $userId,
                            'transaction_id' => $transactionId
                        ]);
                    }
                }

                if (!$userId || !$transactionId) {
                    logPayMongoTransaction('Checkout.Payment.Paid - Transaction not found in database', [
                        'payment_intent_id' => $paymentIntentId,
                        'checkout_session_id' => $eventData['id'] ?? null
                    ]);
                    return; // Can't process without knowing the user
                }
            }
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
        // Try different possible locations for payment_intent_id
        $paymentIntentId = $eventData['attributes']['payment_intent_id'] ??
                          $eventData['payment_intent_id'] ??
                          $eventData['attributes']['source']['payment_intent_id'] ??
                          '';

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

        // Check for user_id in metadata first
        $metadata = $paymentIntent['attributes']['metadata'] ?? [];
        $userId = $metadata['user_id'] ?? null;
        $transactionId = $metadata['transaction_id'] ?? null;

        // If no user_id in metadata, look up from database by payment_intent_id
        if (!$userId) {
            logPayMongoTransaction('Payment.Paid - No user_id in metadata, looking up from database', [
                'payment_intent_id' => $paymentIntentId
            ]);
            
            $database = new Database();
            $pdo = $database->getConnection();
            
            $stmt = $pdo->prepare('SELECT id, user_id, card_id FROM transactions WHERE payment_intent_id = ? LIMIT 1');
            $stmt->execute([$paymentIntentId]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($transaction) {
                $userId = $transaction['user_id'];
                $transactionId = $transaction['id'];
                logPayMongoTransaction('Payment.Paid - Found transaction in database', [
                    'payment_intent_id' => $paymentIntentId,
                    'user_id' => $userId,
                    'transaction_id' => $transactionId
                ]);
            } else {
                logPayMongoTransaction('Payment.Paid - Transaction not found in database', [
                    'payment_intent_id' => $paymentIntentId
                ]);
                return; // Can't process without knowing the user
            }
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