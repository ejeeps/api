<?php
/**
 * PayMongo Webhook Handler
 * 
 * This controller handles PayMongo webhook events to process payments
 * and update user balances when payments are successful.
 */

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../services/PayMongoService.php';

// Set content type for webhook response
header('Content-Type: application/json');

// Log webhook received
logPayMongoTransaction('Webhook Received', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'raw_input' => file_get_contents('php://input')
]);

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get raw POST data
    $rawPayload = file_get_contents('php://input');
    
    if (empty($rawPayload)) {
        throw new Exception('Empty payload received');
    }

    // Parse JSON payload
    $payload = json_decode($rawPayload, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON payload: ' . json_last_error_msg());
    }

    // Validate webhook signature (if webhook secret is configured)
    $payMongoService = new PayMongoService();
    $headers = getallheaders();
    
    if (!empty(PAYMONGO_WEBHOOK_SECRET) && isset($headers['Paymongo-Signature'])) {
        if (!$payMongoService->validateWebhook($rawPayload, $headers['Paymongo-Signature'])) {
            logPayMongoTransaction('Webhook Signature Validation Failed', [
                'signature' => $headers['Paymongo-Signature'] ?? 'missing'
            ]);
            
            http_response_code(401);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }
    }

    // Extract event data
    $eventType = $payload['data']['attributes']['type'] ?? '';
    $eventData = $payload['data']['attributes']['data'] ?? [];

    logPayMongoTransaction('Processing Webhook Event', [
        'event_type' => $eventType,
        'event_id' => $payload['data']['id'] ?? 'unknown'
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

        case 'qrph.expired':
        case 'payment_method.expired':
            handlePaymentExpired($eventData, $payMongoService);
            break;

        default:
            logPayMongoTransaction('Unhandled Webhook Event', [
                'event_type' => $eventType,
                'payload' => $payload
            ]);
            break;
    }

    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Webhook processed']);

} catch (Exception $e) {
    logPayMongoTransaction('Webhook Processing Error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    // Always return 200 to prevent PayMongo from retrying
    // The error is logged for debugging
    http_response_code(200);
    echo json_encode(['status' => 'received', 'message' => 'Webhook received but processing failed']);
}

/**
 * Handle successful payment intent
 */
function handlePaymentSucceeded($eventData, $payMongoService) {
    try {
        $paymentIntentId = $eventData['id'] ?? '';
        $metadata = $eventData['attributes']['metadata'] ?? [];
        $userId = $metadata['user_id'] ?? null;

        if (!$paymentIntentId || !$userId) {
            throw new Exception('Missing payment intent ID or user ID');
        }

        logPayMongoTransaction('Processing Payment Success', [
            'payment_intent_id' => $paymentIntentId,
            'user_id' => $userId
        ]);

        // Process the successful payment
        $result = $payMongoService->processSuccessfulPayment($paymentIntentId, $userId);

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
                logPayMongoTransaction('Checkout.Payment.Paid - Transaction not found in database', [
                    'payment_intent_id' => $paymentIntentId
                ]);
                return; // Can't process without knowing the user
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