<?php
/**
 * PayMongo Service Class
 * 
 * This class handles all PayMongo API interactions including:
 * - Creating payment intents
 * - Creating checkout sessions
 * - Retrieving payment status
 * - Handling webhooks
 */

require_once __DIR__ . '/../config/paymongo.php';
require_once __DIR__ . '/../config/connection.php';

class PayMongoService {
    private $secretKey;
    private $publicKey;
    private $baseUrl;
    private $db;

    public function __construct() {
        $this->secretKey = PAYMONGO_SECRET_KEY;
        $this->publicKey = PAYMONGO_PUBLIC_KEY;
        $this->baseUrl = PAYMONGO_BASE_URL;
        
        // Initialize database connection
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Create a payment intent
     */
    public function createPaymentIntent($amount, $currency = 'PHP', $description = 'Points Purchase', $metadata = []) {
        $url = $this->baseUrl . '/payment_intents';
        
        $data = [
            'data' => [
                'attributes' => [
                    'amount' => convertToCentavos($amount),
                    'payment_method_allowed' => PAYMONGO_PAYMENT_METHODS,
                    'payment_method_options' => [
                        'card' => [
                            'request_three_d_secure' => 'automatic'
                        ]
                    ],
                    'currency' => $currency,
                    'capture_type' => 'automatic',
                    'description' => $description,
                    'metadata' => $metadata
                ]
            ]
        ];

        $response = $this->makeRequest('POST', $url, $data);
        
        if ($response && isset($response['data'])) {
            logPayMongoTransaction('Payment Intent Created', [
                'payment_intent_id' => $response['data']['id'],
                'amount' => $amount,
                'metadata' => $metadata
            ]);
            return $response['data'];
        }

        return false;
    }

    /**
     * Create a checkout session
     */
    public function createCheckoutSession($paymentIntentId, $successUrl = null, $cancelUrl = null, $amount = null, $mobileOptimized = false) {
        $url = $this->baseUrl . '/checkout_sessions';
        
        // Use mobile-optimized payment methods if requested
        $paymentMethods = $mobileOptimized && defined('PAYMONGO_MOBILE_METHODS') 
            ? PAYMONGO_MOBILE_METHODS 
            : PAYMONGO_PAYMENT_METHODS;
        
        $data = [
            'data' => [
                'attributes' => [
                    'payment_intent_id' => $paymentIntentId,
                    'payment_method_types' => $paymentMethods,
                    'success_url' => $successUrl ?: PAYMONGO_SUCCESS_URL,
                    'cancel_url' => $cancelUrl ?: PAYMONGO_CANCEL_URL,
                    'description' => 'E-JEEP Points Purchase',
                    'send_email_receipt' => false,
                    'show_description' => true,
                    'show_line_items' => true,
                    'reference_number' => generateTransactionReference(time()),
                    'statement_descriptor' => 'EJEEP-POINTS',
                    // Force all payment methods to be visible on mobile
                    'payment_method_options' => [
                        'card' => [
                            'request_three_d_secure' => 'automatic'
                        ]
                    ],
                    // Mobile-specific configurations
                    'billing' => [
                        'name' => 'E-JEEP Customer',
                        'email' => 'customer@ejeep.com'
                    ],
                    // Additional mobile optimization
                    'metadata' => [
                        'mobile_optimized' => 'true',
                        'force_show_all_methods' => 'true'
                    ]
                ]
            ]
        ];
        
        // Add line items if amount is provided
        if ($amount) {
            $data['data']['attributes']['line_items'] = [
                [
                    'name' => 'E-JEEP Points Top-up',
                    'quantity' => 1,
                    'amount' => convertToCentavos($amount),
                    'currency' => 'PHP',
                    'description' => 'Points credit for E-JEEP card'
                ]
            ];
        }

        $response = $this->makeRequest('POST', $url, $data);
        
        if ($response && isset($response['data'])) {
            logPayMongoTransaction('Checkout Session Created', [
                'checkout_session_id' => $response['data']['id'],
                'payment_intent_id' => $paymentIntentId
            ]);
            return $response['data'];
        }

        return false;
    }

    /**
     * Retrieve payment intent
     */
    public function getPaymentIntent($paymentIntentId) {
        $url = $this->baseUrl . '/payment_intents/' . $paymentIntentId;
        $response = $this->makeRequest('GET', $url);
        
        if ($response && isset($response['data'])) {
            return $response['data'];
        }

        return false;
    }

    /**
     * Retrieve checkout session
     */
    public function getCheckoutSession($checkoutSessionId) {
        $url = $this->baseUrl . '/checkout_sessions/' . $checkoutSessionId;
        $response = $this->makeRequest('GET', $url);
        
        if ($response && isset($response['data'])) {
            return $response['data'];
        }

        return false;
    }

    /**
     * Create a webhook
     */
    public function createWebhook($events = ['payment_intent.succeeded', 'payment_intent.payment_failed']) {
        $url = $this->baseUrl . '/webhooks';
        
        $data = [
            'data' => [
                'attributes' => [
                    'url' => PAYMONGO_WEBHOOK_URL,
                    'events' => $events
                ]
            ]
        ];

        $response = $this->makeRequest('POST', $url, $data);
        
        if ($response && isset($response['data'])) {
            logPayMongoTransaction('Webhook Created', [
                'webhook_id' => $response['data']['id'],
                'events' => $events
            ]);
            return $response['data'];
        }

        return false;
    }

    /**
     * Process successful payment
     */
    public function processSuccessfulPayment($paymentIntentId, $userId) {
        try {
            // Get payment intent details
            $paymentIntent = $this->getPaymentIntent($paymentIntentId);
            
            if (!$paymentIntent || $paymentIntent['attributes']['status'] !== 'succeeded') {
                throw new Exception('Payment not successful');
            }

            $amount = convertToPesos($paymentIntent['attributes']['amount']);
            $metadata = $paymentIntent['attributes']['metadata'];
            
            // Start transaction
            $this->db->beginTransaction();

            // Get passenger's card information
            $stmt = $this->db->prepare("
                SELECT c.id as card_id, c.balance 
                FROM passengers p 
                JOIN card_assign_passengers cap ON p.id = cap.passenger_id 
                JOIN cards c ON cap.card_id = c.id 
                WHERE p.user_id = ? AND cap.status = 'active' AND c.status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $cardInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$cardInfo) {
                throw new Exception('No active card found for user');
            }

            // Update card balance
            $newBalance = $cardInfo['balance'] + $amount;
            $stmt = $this->db->prepare("UPDATE cards SET balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $cardInfo['card_id']]);

            // Create transaction record
            $transactionRef = generateTransactionReference($userId);
            $stmt = $this->db->prepare("
                INSERT INTO transactions (
                    user_id, card_id, transaction_reference, payment_intent_id,
                    amount, transaction_type, status, payment_method, 
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, 'top_up', 'completed', 'paymongo', NOW(), NOW())
            ");
            $stmt->execute([
                $userId, 
                $cardInfo['card_id'], 
                $transactionRef, 
                $paymentIntentId, 
                $amount
            ]);

            $this->db->commit();

            logPayMongoTransaction('Payment Processed Successfully', [
                'user_id' => $userId,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'transaction_ref' => $transactionRef
            ]);

            return [
                'success' => true,
                'amount' => $amount,
                'new_balance' => $newBalance,
                'transaction_reference' => $transactionRef
            ];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            
            logPayMongoTransaction('Payment Processing Failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'payment_intent_id' => $paymentIntentId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Make HTTP request to PayMongo API
     */
    private function makeRequest($method, $url, $data = null) {
        $ch = curl_init();
        
        $headers = [
            'Authorization: ' . getPayMongoAuthHeader(),
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method
        ]);

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            logPayMongoTransaction('cURL Error', ['error' => $error, 'url' => $url]);
            return false;
        }

        $decodedResponse = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorDetails = [
                'http_code' => $httpCode,
                'url' => $url,
                'method' => $method
            ];
            
            // Extract specific error messages from PayMongo response
            if ($decodedResponse && isset($decodedResponse['errors'])) {
                $errorDetails['errors'] = [];
                foreach ($decodedResponse['errors'] as $error) {
                    $errorDetails['errors'][] = [
                        'code' => $error['code'] ?? 'unknown',
                        'detail' => $error['detail'] ?? 'No details provided',
                        'source' => $error['source'] ?? null
                    ];
                }
            } else {
                $errorDetails['raw_response'] = $decodedResponse;
            }
            
            logPayMongoTransaction('PayMongo API Error', $errorDetails);
            return false;
        }

        return $decodedResponse;
    }

    /**
     * Validate webhook signature
     */
    public function validateWebhook($payload, $signature) {
        if (empty(PAYMONGO_WEBHOOK_SECRET)) {
            logPayMongoTransaction('Webhook Validation Failed', ['reason' => 'No webhook secret configured']);
            return false;
        }

        return validateWebhookSignature($payload, $signature, PAYMONGO_WEBHOOK_SECRET);
    }
}