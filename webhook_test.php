<?php
/**
 * Webhook Test Endpoint
 * Use this to verify webhooks can reach your server
 */

// Log all requests
$logFile = __DIR__ . '/logs/webhook_test.log';
$logDir = dirname($logFile);

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$timestamp = date('Y-m-d H:i:s');
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$rawInput = file_get_contents('php://input');

$logEntry = "[{$timestamp}] Method: {$method}\n";
$logEntry .= "Headers: " . json_encode($headers) . "\n";
$logEntry .= "Body: " . $rawInput . "\n";
$logEntry .= "----------------------------------------\n";

file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// Return success
header('Content-Type: application/json');
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'message' => 'Webhook test received',
    'timestamp' => $timestamp,
    'method' => $method
]);
