<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - E-JEEP</title>
    <?php
    // Get base path - works when included from index.php or accessed directly
    $basePath = '';
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        // If accessed via index.php?page=terms, use root path
        if (strpos($scriptPath, 'index.php') !== false) {
            $basePath = '';
        } else {
            // If accessed directly, go up to root
            $basePath = '../../';
        }
    }
    ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath); ?>assets/style/index.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath); ?>assets/style/driver.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath); ?>assets/style/legal.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="<?php echo htmlspecialchars($basePath); ?>index.php">
                    <img src="<?php echo htmlspecialchars($basePath); ?>assets/logo.png" alt="E-JEEP Logo" onerror="this.outerHTML='<span class=\'logo-text\'>E-JEEP</span>';">
                </a>
            </div>
            <div class="nav-links">
                <a href="<?php echo htmlspecialchars($basePath); ?>index.php" class="nav-link">Home</a>
                <a href="#" class="nav-link">Contact</a>
                <a href="<?php echo htmlspecialchars($basePath); ?>index.php?login=1" class="nav-link nav-link-login">Login</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="legal-content">
                <div class="legal-header">
                    <h1 class="legal-title">Terms and Conditions</h1>
                    <p class="legal-subtitle">Last Updated: <?php echo date('F d, Y'); ?></p>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">1. Acceptance of Terms</h2>
                    <div class="legal-section-content">
                        <p>By accessing and using the E-JEEP platform, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">2. Description of Service</h2>
                    <div class="legal-section-content">
                        <p>E-JEEP is a cashless payment system for jeepney transportation. The platform provides:</p>
                        <ul>
                            <li>E-JEEP cards for passengers to make cashless payments</li>
                            <li>Card management and balance reloading services</li>
                            <li>Transaction history and account management</li>
                            <li>Driver registration and device assignment for accepting card payments</li>
                        </ul>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">3. User Accounts</h2>
                    <div class="legal-section-content">
                        <p><strong>3.1 Registration:</strong> To use E-JEEP services, you must register for an account and provide accurate, current, and complete information.</p>
                        <p><strong>3.2 Account Security:</strong> You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>
                        <p><strong>3.3 Account Termination:</strong> We reserve the right to suspend or terminate your account at any time for violation of these terms or for any other reason we deem necessary.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">4. E-JEEP Card Usage</h2>
                    <div class="legal-section-content">
                        <p><strong>4.1 Card Issuance:</strong> E-JEEP cards are issued subject to approval and verification of your registration information.</p>
                        <p><strong>4.2 Card Balance:</strong> You are responsible for maintaining sufficient balance on your card. We are not liable for declined transactions due to insufficient balance.</p>
                        <p><strong>4.3 Card Loss or Theft:</strong> You must immediately report lost or stolen cards. We are not responsible for unauthorized transactions made before you report the loss.</p>
                        <p><strong>4.4 Card Expiry:</strong> Cards may have an expiration date. You will be notified before expiration and can request a replacement card.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">5. Payments and Refunds</h2>
                    <div class="legal-section-content">
                        <p><strong>5.1 Reloading:</strong> You can reload your card balance through authorized payment methods. Minimum reload amount is ₱50.00.</p>
                        <p><strong>5.2 Transaction Fees:</strong> Some transactions may be subject to fees. All fees will be clearly disclosed before you complete a transaction.</p>
                        <p><strong>5.3 Refunds:</strong> Refunds are processed according to our refund policy. Contact support for refund requests.</p>
                        <p><strong>5.4 Disputes:</strong> If you dispute a transaction, contact us within 30 days of the transaction date.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">6. Driver Responsibilities</h2>
                    <div class="legal-section-content">
                        <p><strong>6.1 Driver Registration:</strong> Drivers must provide valid driver's license and complete background check requirements.</p>
                        <p><strong>6.2 Device Usage:</strong> Drivers are responsible for the assigned device and must use it in accordance with our guidelines.</p>
                        <p><strong>6.3 Payment Processing:</strong> Drivers must accept E-JEEP card payments from passengers and process transactions correctly.</p>
                        <p><strong>6.4 Compliance:</strong> Drivers must comply with all transportation regulations and maintain valid licenses.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">7. Prohibited Activities</h2>
                    <div class="legal-section-content">
                        <p>You agree not to:</p>
                        <ul>
                            <li>Use the service for any illegal or unauthorized purpose</li>
                            <li>Attempt to hack, breach, or compromise the security of the system</li>
                            <li>Create fake accounts or provide false information</li>
                            <li>Transfer or sell your account or card to another person</li>
                            <li>Use the service to commit fraud or other criminal activities</li>
                            <li>Interfere with or disrupt the service or servers</li>
                        </ul>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">8. Limitation of Liability</h2>
                    <div class="legal-section-content">
                        <p>E-JEEP shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of or inability to use the service. Our total liability shall not exceed the amount you paid for the service in the past 12 months.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">9. Changes to Terms</h2>
                    <div class="legal-section-content">
                        <p>We reserve the right to modify these terms at any time. We will notify users of significant changes via email or through the platform. Continued use of the service after changes constitutes acceptance of the new terms.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">10. Contact Information</h2>
                    <div class="legal-section-content">
                        <p>For questions about these Terms and Conditions, please contact us at:</p>
                        <p><strong>Email:</strong> support@ejeep.ph<br>
                        <strong>Phone:</strong> (02) 1234-5678<br>
                        <strong>Address:</strong> E-JEEP Headquarters, Metro Manila, Philippines</p>
                    </div>
                </div>

                <a href="<?php echo htmlspecialchars($basePath); ?>index.php" class="back-link">← Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>

