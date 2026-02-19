<?php
/**
 * PayMongo Setup Script
 * 
 * This script helps set up PayMongo integration by:
 * 1. Running the transactions table migration
 * 2. Creating PayMongo webhooks
 * 3. Testing the configuration
 */

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../services/PayMongoService.php';

// Set content type
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>";
echo "<html><head><title>PayMongo Setup</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;line-height:1.6;}";
echo ".success{color:#28a745;background:#d4edda;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".info{color:#0c5460;background:#d1ecf1;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".code{background:#f8f9fa;padding:10px;border-radius:5px;font-family:monospace;margin:10px 0;}";
echo "</style></head><body>";

echo "<h1>PayMongo Integration Setup</h1>";

// Step 1: Check database connection
echo "<h2>Step 1: Database Connection</h2>";
try {
    $database = new Database();
    $pdo = $database->getConnection();
    echo "<div class='success'>✓ Database connection successful</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</body></html>";
    exit;
}

// Step 2: Run transactions table migration
echo "<h2>Step 2: Create Transactions Table</h2>";
try {
    $migrationSql = file_get_contents(__DIR__ . '/../database/migrations/023_create_transactions_table.sql');
    
    // Remove comments and split by semicolon
    $statements = array_filter(
        array_map('trim', explode(';', $migrationSql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^\s*--/', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (!empty(trim($statement))) {
            $pdo->exec($statement);
        }
    }
    
    echo "<div class='success'>✓ Transactions table created successfully</div>";
} catch (Exception $e) {
    echo "<div class='error'>✗ Failed to create transactions table: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Step 3: Test PayMongo configuration
echo "<h2>Step 3: PayMongo Configuration Test</h2>";
try {
    $payMongoService = new PayMongoService();
    
    // Test API connection by creating a test payment intent (but don't process it)
    echo "<div class='info'>Testing PayMongo API connection...</div>";
    
    $testPaymentIntent = $payMongoService->createPaymentIntent(
        50.00, // Test amount
        'PHP',
        'Test Payment Intent - Setup',
        ['test' => true, 'setup' => true]
    );
    
    if ($testPaymentIntent) {
        echo "<div class='success'>✓ PayMongo API connection successful</div>";
        echo "<div class='info'>Test Payment Intent ID: " . htmlspecialchars($testPaymentIntent['id']) . "</div>";
    } else {
        echo "<div class='error'>✗ PayMongo API connection failed</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ PayMongo configuration error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Step 4: Create webhook (optional)
echo "<h2>Step 4: Create PayMongo Webhook (Optional)</h2>";
echo "<div class='info'>To automatically process payments, you need to create a webhook in PayMongo.</div>";

if (isset($_POST['create_webhook'])) {
    try {
        $webhook = $payMongoService->createWebhook([
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'checkout_session.payment_paid'
        ]);
        
        if ($webhook) {
            echo "<div class='success'>✓ Webhook created successfully</div>";
            echo "<div class='info'>Webhook ID: " . htmlspecialchars($webhook['id']) . "</div>";
            echo "<div class='info'>Webhook URL: " . htmlspecialchars($webhook['attributes']['url']) . "</div>";
            echo "<div class='info'>Webhook Secret: " . htmlspecialchars($webhook['attributes']['secret_key']) . "</div>";
            echo "<div class='error'>IMPORTANT: Copy the webhook secret above and update it in config/paymongo.php</div>";
        } else {
            echo "<div class='error'>✗ Failed to create webhook</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>✗ Webhook creation error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<form method='POST'>";
    echo "<button type='submit' name='create_webhook' style='background:#007bff;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;'>Create Webhook</button>";
    echo "</form>";
}

// Step 5: Configuration summary
echo "<h2>Step 5: Configuration Summary</h2>";
echo "<div class='info'>";
echo "<strong>PayMongo Configuration:</strong><br>";
echo "• Mode: " . (defined('PAYMONGO_MODE') ? '<strong>' . strtoupper(PAYMONGO_MODE) . '</strong>' : 'Not configured') . "<br>";
echo "• Secret Key: " . (defined('PAYMONGO_SECRET_KEY') ? 'Configured (' . substr(PAYMONGO_SECRET_KEY, 0, 15) . '...)' : 'Not configured') . "<br>";
echo "• Public Key: " . (defined('PAYMONGO_PUBLIC_KEY') ? 'Configured (' . substr(PAYMONGO_PUBLIC_KEY, 0, 15) . '...)' : 'Not configured') . "<br>";
echo "• Base URL: " . (defined('PAYMONGO_BASE_URL') ? PAYMONGO_BASE_URL : 'Not configured') . "<br>";
echo "• Success URL: " . (defined('PAYMONGO_SUCCESS_URL') ? PAYMONGO_SUCCESS_URL : 'Not configured') . "<br>";
echo "• Cancel URL: " . (defined('PAYMONGO_CANCEL_URL') ? PAYMONGO_CANCEL_URL : 'Not configured') . "<br>";
echo "• Webhook URL: " . (defined('PAYMONGO_WEBHOOK_URL') ? PAYMONGO_WEBHOOK_URL : 'Not configured') . "<br>";
echo "</div>";

if (defined('PAYMONGO_MODE') && PAYMONGO_MODE === 'test') {
    echo "<div class='success'>";
    echo "<strong>🧪 Test Mode Active:</strong><br>";
    echo "• Safe for development and testing<br>";
    echo "• No real money will be charged<br>";
    echo "• You can use test card numbers<br>";
    echo "• Perfect for debugging payment flows";
    echo "</div>";
}

// Step 6: Next steps
echo "<h2>Next Steps</h2>";
echo "<div class='info'>";
echo "<ol>";
echo "<li>If you created a webhook above, copy the webhook secret and update <code>PAYMONGO_WEBHOOK_SECRET</code> in <code>config/paymongo.php</code></li>";
echo "<li>Update the <code>APP_BASE_URL</code> in <code>config/paymongo.php</code> to match your actual domain</li>";
echo "<li>Test the buy points feature by going to the passenger dashboard</li>";
echo "<li>Monitor the logs in <code>logs/paymongo.log</code> for any issues</li>";
echo "</ol>";
echo "</div>";

echo "<h2>Test Links</h2>";
echo "<div class='info'>";
echo "<a href='../../index.php?page=buypoints' style='color:#007bff;'>Test Buy Points (Dashboard)</a><br>";
echo "<a href='mobile_buypoints.php' style='color:#007bff;'>Test Buy Points (Direct)</a>";
echo "</div>";

echo "<div class='code'>";
echo "<strong>Sample webhook secret update:</strong><br>";
echo "define('PAYMONGO_WEBHOOK_SECRET', 'whsec_your_webhook_secret_here');";
echo "</div>";

echo "</body></html>";
?>
