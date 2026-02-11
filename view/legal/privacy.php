<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - E-JEEP</title>
    <?php
    // Get base path - works when included from index.php or accessed directly
    $basePath = '';
    if (isset($_SERVER['SCRIPT_NAME'])) {
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        // If accessed via index.php?page=privacy, use root path
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
                    <h1 class="legal-title">Privacy Policy</h1>
                    <p class="legal-subtitle">Last Updated: <?php echo date('F d, Y'); ?></p>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">1. Introduction</h2>
                    <div class="legal-section-content">
                        <p>E-JEEP ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform and services.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">2. Information We Collect</h2>
                    <div class="legal-section-content">
                        <p><strong>2.1 Personal Information:</strong> We collect information that you provide directly to us, including:</p>
                        <ul>
                            <li>Name, email address, phone number, and date of birth</li>
                            <li>Address and location information</li>
                            <li>Government-issued ID numbers and images (for verification)</li>
                            <li>Driver's license information (for drivers)</li>
                            <li>Profile photographs</li>
                            <li>Payment information and transaction history</li>
                        </ul>
                        <p><strong>2.2 Usage Information:</strong> We automatically collect information about how you use our service, including:</p>
                        <ul>
                            <li>Transaction records and card usage</li>
                            <li>Device information and IP address</li>
                            <li>Location data (when using location-based features)</li>
                            <li>Log files and analytics data</li>
                        </ul>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">3. How We Use Your Information</h2>
                    <div class="legal-section-content">
                        <p>We use the information we collect to:</p>
                        <ul>
                            <li>Provide, maintain, and improve our services</li>
                            <li>Process transactions and manage your account</li>
                            <li>Verify your identity and prevent fraud</li>
                            <li>Send you important updates and notifications</li>
                            <li>Respond to your inquiries and provide customer support</li>
                            <li>Comply with legal obligations and enforce our terms</li>
                            <li>Analyze usage patterns to improve user experience</li>
                        </ul>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">4. Information Sharing and Disclosure</h2>
                    <div class="legal-section-content">
                        <p>We do not sell your personal information. We may share your information only in the following circumstances:</p>
                        <ul>
                            <li><strong>Service Providers:</strong> With third-party service providers who perform services on our behalf (payment processing, data storage, etc.)</li>
                            <li><strong>Legal Requirements:</strong> When required by law, court order, or government regulation</li>
                            <li><strong>Business Transfers:</strong> In connection with a merger, acquisition, or sale of assets</li>
                            <li><strong>With Your Consent:</strong> When you explicitly authorize us to share your information</li>
                            <li><strong>Safety and Security:</strong> To protect the rights, property, or safety of E-JEEP, our users, or others</li>
                        </ul>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">5. Data Security</h2>
                    <div class="legal-section-content">
                        <p>We implement appropriate technical and organizational security measures to protect your personal information, including:</p>
                        <ul>
                            <li>Encryption of sensitive data in transit and at rest</li>
                            <li>Secure authentication and access controls</li>
                            <li>Regular security audits and assessments</li>
                            <li>Employee training on data protection</li>
                        </ul>
                        <p>However, no method of transmission over the internet or electronic storage is 100% secure. While we strive to protect your information, we cannot guarantee absolute security.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">6. Data Retention</h2>
                    <div class="legal-section-content">
                        <p>We retain your personal information for as long as necessary to:</p>
                        <ul>
                            <li>Provide our services to you</li>
                            <li>Comply with legal obligations</li>
                            <li>Resolve disputes and enforce agreements</li>
                            <li>Maintain transaction records as required by law</li>
                        </ul>
                        <p>When you close your account, we will delete or anonymize your information, except where we are required to retain it for legal purposes.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">7. Your Rights and Choices</h2>
                    <div class="legal-section-content">
                        <p>You have the following rights regarding your personal information:</p>
                        <ul>
                            <li><strong>Access:</strong> Request access to your personal information</li>
                            <li><strong>Correction:</strong> Request correction of inaccurate or incomplete information</li>
                            <li><strong>Deletion:</strong> Request deletion of your personal information (subject to legal requirements)</li>
                            <li><strong>Objection:</strong> Object to processing of your information for certain purposes</li>
                            <li><strong>Data Portability:</strong> Request a copy of your data in a portable format</li>
                            <li><strong>Withdraw Consent:</strong> Withdraw consent where processing is based on consent</li>
                        </ul>
                        <p>To exercise these rights, please contact us using the information provided in Section 10.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">8. Cookies and Tracking Technologies</h2>
                    <div class="legal-section-content">
                        <p>We use cookies and similar tracking technologies to track activity on our platform and store certain information. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent. However, if you do not accept cookies, you may not be able to use some portions of our service.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">9. Children's Privacy</h2>
                    <div class="legal-section-content">
                        <p>Our service is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13. If you are a parent or guardian and believe your child has provided us with personal information, please contact us immediately.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">10. Changes to This Privacy Policy</h2>
                    <div class="legal-section-content">
                        <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last Updated" date. You are advised to review this Privacy Policy periodically for any changes.</p>
                    </div>
                </div>

                <div class="legal-section">
                    <h2 class="legal-section-title">11. Contact Us</h2>
                    <div class="legal-section-content">
                        <p>If you have any questions about this Privacy Policy or wish to exercise your rights, please contact us at:</p>
                        <p><strong>Email:</strong> privacy@ejeep.ph<br>
                        <strong>Phone:</strong> (02) 1234-5678<br>
                        <strong>Address:</strong> E-JEEP Headquarters, Metro Manila, Philippines</p>
                    </div>
                </div>

                
            </div>
        </div>
    </div>
</body>
</html>

