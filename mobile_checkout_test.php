<?php
/**
 * Mobile Checkout Test
 * This page helps test the mobile checkout experience
 */

require_once __DIR__ . '/config/paymongo.php';

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Mobile Checkout Test</title>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<style>";
echo "body{font-family:Arial,sans-serif;padding:20px;max-width:400px;margin:0 auto;}";
echo ".info{background:#d1ecf1;padding:15px;border-radius:8px;margin:10px 0;}";
echo ".payment-methods{background:#f8f9fa;padding:15px;border-radius:8px;margin:10px 0;}";
echo ".method{padding:8px;background:white;margin:5px 0;border-radius:5px;border:1px solid #ddd;}";
echo ".test-btn{background:#007bff;color:white;padding:15px;border:none;border-radius:8px;width:100%;font-size:16px;margin:10px 0;}";
echo "</style>";
echo "</head><body>";

echo "<h2>📱 Mobile Checkout Test</h2>";

echo "<div class='info'>";
echo "<strong>Current Mode:</strong> " . PAYMONGO_MODE . "<br>";
echo "<strong>Payment Methods:</strong> " . count(PAYMONGO_PAYMENT_METHODS) . " available";
echo "</div>";

echo "<div class='payment-methods'>";
echo "<strong>Available Payment Methods:</strong><br>";
foreach (PAYMONGO_PAYMENT_METHODS as $index => $method) {
    $priority = $index + 1;
    echo "<div class='method'>$priority. " . strtoupper(str_replace('_', ' ', $method)) . "</div>";
}
echo "</div>";

echo "<div class='info'>";
echo "<strong>💡 Mobile Tips:</strong><br>";
echo "• PayMongo may show only the first payment method initially<br>";
echo "• Look for 'Pay Another Way' or 'Other Payment Methods' button<br>";
echo "• Try scrolling down on the payment page<br>";
echo "• Some methods may be region/device specific";
echo "</div>";

// Create a test checkout session for mobile
if (isset($_GET['create_test'])) {
    try {
        require_once __DIR__ . '/services/PayMongoService.php';
        
        $payMongoService = new PayMongoService();
        
        // Create payment intent
        $paymentIntent = $payMongoService->createPaymentIntent(
            100.00,
            'PHP',
            'Mobile Test - ₱100.00',
            [
                'test_mode' => 'mobile',
                'user_id' => '999',
                'device' => 'mobile_test'
            ]
        );
        
        if ($paymentIntent) {
            // Create checkout session
            $checkoutSession = $payMongoService->createCheckoutSession(
                $paymentIntent['id'],
                null,
                null,
                100.00
            );
            
            if ($checkoutSession) {
                echo "<div class='info'>";
                echo "<strong>✅ Test Checkout Created!</strong><br>";
                echo "Payment Methods: " . implode(', ', $paymentIntent['attributes']['payment_method_allowed']) . "<br>";
                echo "<a href='" . $checkoutSession['attributes']['checkout_url'] . "' target='_blank' style='background:#28a745;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;display:inline-block;margin-top:10px;'>Open Mobile Checkout</a>";
                echo "</div>";
            } else {
                echo "<div style='color:red;'>Failed to create checkout session</div>";
            }
        } else {
            echo "<div style='color:red;'>Failed to create payment intent</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if (!isset($_GET['create_test'])) {
    echo "<a href='?create_test=1' class='test-btn'>🧪 Create Mobile Test Checkout</a>";
}

echo "<div class='info'>";
echo "<strong>🔧 Troubleshooting Mobile Issues:</strong><br><br>";
echo "<strong>If 'Pay Another Way' is missing:</strong><br>";
echo "1. Check if your PayMongo account has all payment methods enabled<br>";
echo "2. Some payment methods may be disabled for test mode<br>";
echo "3. PayMongo may prioritize mobile-friendly methods (GCash, PayMaya)<br>";
echo "4. Try different amounts - some methods have minimum limits<br><br>";
echo "<strong>Common Mobile Behavior:</strong><br>";
echo "• GCash/PayMaya show first (most popular in PH)<br>";
echo "• Cards may require scrolling or 'More Options'<br>";
echo "• QRPh works well on mobile devices<br>";
echo "• Some methods may redirect to mobile apps";
echo "</div>";

echo "</body></html>";
?>