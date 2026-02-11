<?php
/**
 * PayMongo Configuration
 * 
 * This file contains PayMongo API configuration and helper functions
 */

// PayMongo Environment Detection
$isLocal = in_array($_SERVER['SERVER_NAME'], [
    'localhost',
    '192.168.0.103',
    '127.0.0.1'
]);

// PayMongo API Configuration
if ($isLocal) {
    // TEST MODE (for development)
    define('PAYMONGO_SECRET_KEY', 'sk_test_oMcNDGWQVGB7fosR1s2QJFU3');
    define('PAYMONGO_PUBLIC_KEY', 'pk_test_7BJwe54nAcQbXDLYXYAkEkGU');
    define('PAYMONGO_MODE', 'test');
} else {
    // LIVE MODE (for production)
    define('PAYMONGO_SECRET_KEY', 'sk_live_DxN7AUY9EzyaF8QpLfLQvHm6');
    define('PAYMONGO_PUBLIC_KEY', 'pk_live_myEYtWqEx9Muy9phzNESXAUc');
    define('PAYMONGO_MODE', 'live');
}

define('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1');

// PayMongo Webhook Configuration
define('PAYMONGO_WEBHOOK_SECRET', ''); // You'll need to set this after creating webhooks

// Application Configuration
define('APP_BASE_URL', 'http://localhost/api'); // Change this to your actual domain
define('PAYMONGO_SUCCESS_URL', APP_BASE_URL . '/controller/passenger/PayMongoCallbackController.php?status=success');
define('PAYMONGO_CANCEL_URL', APP_BASE_URL . '/controller/passenger/PayMongoCallbackController.php?status=cancel');
define('PAYMONGO_WEBHOOK_URL', APP_BASE_URL . '/controller/passenger/PayMongoWebhookController.php');

// Supported Payment Methods
define('PAYMONGO_PAYMENT_METHODS', [
    'card',
    'gcash',
    'grab_pay',
    'paymaya',
    'qrph',
    'billease'
]);

/**
 * Get PayMongo authorization header
 */
function getPayMongoAuthHeader() {
    return 'Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':');
}

/**
 * Convert amount to centavos (PayMongo requires amounts in centavos)
 */
function convertToCentavos($amount) {
    return intval($amount * 100);
}

/**
 * Convert amount from centavos to pesos
 */
function convertToPesos($centavos) {
    return $centavos / 100;
}

/**
 * Generate a unique reference number for transactions
 */
function generateTransactionReference($userId) {
    return 'TXN_' . $userId . '_' . time() . '_' . rand(1000, 9999);
}

/**
 * Validate PayMongo webhook signature
 */
function validateWebhookSignature($payload, $signature, $secret) {
    $computedSignature = hash_hmac('sha256', $payload, $secret);
    return hash_equals($signature, $computedSignature);
}

/**
 * Log PayMongo transactions for debugging
 */
function logPayMongoTransaction($message, $data = []) {
    $logFile = __DIR__ . '/../logs/paymongo.log';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $mode = defined('PAYMONGO_MODE') ? '[' . strtoupper(PAYMONGO_MODE) . ']' : '[UNKNOWN]';
    $logMessage = "[{$timestamp}] {$mode} {$message}";
    
    if (!empty($data)) {
        $logMessage .= " | Data: " . json_encode($data);
    }
    
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
}