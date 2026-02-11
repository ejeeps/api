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
            handleCheckoutPaymentPaid($eventData, $payMongoService);
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

    http_response_code(500);
    echo json_encode(['error' => 'Webhook processing failed']);
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
            throw new Exception($result['error'] ?? 'Failed to process payment');
        }

    } catch (Exception $e) {
        logPayMongoTransaction('Payment Success Handling Error', [
            'error' => $e->getMessage(),
            'event_data' => $eventData
        ]);
        throw $e;
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
        throw $e;
    }
}

/**
 * Handle checkout session payment paid (alternative event)
 */
function handleCheckoutPaymentPaid($eventData, $payMongoService) {
    try {
        // Extract payment intent from checkout session
        $paymentIntentId = $eventData['attributes']['payment_intent']['id'] ?? '';
        
        if ($paymentIntentId) {
            // Get payment intent details to extract metadata
            $paymentIntent = $payMongoService->getPaymentIntent($paymentIntentId);
            
            if ($paymentIntent && isset($paymentIntent['attributes']['metadata']['user_id'])) {
                $userId = $paymentIntent['attributes']['metadata']['user_id'];
                
                logPayMongoTransaction('Processing Checkout Payment Paid', [
                    'payment_intent_id' => $paymentIntentId,
                    'user_id' => $userId
                ]);

                // Process the successful payment
                $result = $payMongoService->processSuccessfulPayment($paymentIntentId, $userId);

                if (!$result['success']) {
                    throw new Exception($result['error'] ?? 'Failed to process payment');
                }
            }
        }

    } catch (Exception $e) {
        logPayMongoTransaction('Checkout Payment Paid Handling Error', [
            'error' => $e->getMessage(),
            'event_data' => $eventData
        ]);
        throw $e;
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