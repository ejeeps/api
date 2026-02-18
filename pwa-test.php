<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-JEEP PWA Test</title>
    <?php include 'includes/pwa-meta.php'; ?>
    <link rel="stylesheet" href="assets/style/index.css">
    <style>
        .test-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .test-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            border-left: 4px solid #22c55e;
            background: #f0fdf4;
        }
        .test-item.error {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        .test-item.warning {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .status-success { background: #22c55e; }
        .status-error { background: #ef4444; }
        .status-warning { background: #f59e0b; }
        .test-actions {
            margin-top: 30px;
            text-align: center;
        }
        .test-btn {
            background: #22c55e;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
        }
        .test-btn:hover {
            background: #16a34a;
        }
        .test-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 E-JEEP PWA Test Suite</h1>
        <p>This page tests the PWA functionality of your E-JEEP application.</p>
        
        <div id="testResults">
            <div class="test-item">
                <span class="status-indicator status-warning"></span>
                <strong>Running tests...</strong>
            </div>
        </div>
        
        <div class="test-actions">
            <button class="test-btn" onclick="runTests()">Run Tests Again</button>
            <button class="test-btn" onclick="testInstallPrompt()">Test Install Prompt</button>
            <button class="test-btn" onclick="testMobileInstall()">Mobile Install Guide</button>
            <button class="test-btn" onclick="testStandaloneMode()">Test Standalone Mode</button>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h3>Quick Actions</h3>
            <p><a href="index.php">← Back to E-JEEP Home</a></p>
            <p><a href="manifest.json" target="_blank">View Manifest</a> | <a href="sw.js" target="_blank">View Service Worker</a> | <a href="offline.html" target="_blank">View Offline Page</a></p>
        </div>
    </div>

    <script>
        let testResults = [];
        
        function addTestResult(name, status, message) {
            testResults.push({ name, status, message });
            updateTestDisplay();
        }
        
        function updateTestDisplay() {
            const container = document.getElementById('testResults');
            container.innerHTML = testResults.map(test => {
                const statusClass = test.status === 'success' ? 'status-success' : 
                                  test.status === 'error' ? 'status-error' : 'status-warning';
                const itemClass = test.status === 'success' ? '' : 
                                test.status === 'error' ? 'error' : 'warning';
                
                return `
                    <div class="test-item ${itemClass}">
                        <span class="status-indicator ${statusClass}"></span>
                        <strong>${test.name}:</strong> ${test.message}
                    </div>
                `;
            }).join('');
        }
        
        async function runTests() {
            testResults = [];
            addTestResult('Starting Tests', 'warning', 'Running PWA compatibility tests...');
            
            // Test 1: Service Worker Support
            if ('serviceWorker' in navigator) {
                addTestResult('Service Worker Support', 'success', 'Browser supports Service Workers');
                
                try {
                    const registration = await navigator.serviceWorker.getRegistration();
                    if (registration) {
                        addTestResult('Service Worker Registration', 'success', 'Service Worker is registered and active');
                    } else {
                        addTestResult('Service Worker Registration', 'warning', 'Service Worker not yet registered. Try refreshing the main page.');
                    }
                } catch (error) {
                    addTestResult('Service Worker Registration', 'error', 'Error checking Service Worker: ' + error.message);
                }
            } else {
                addTestResult('Service Worker Support', 'error', 'Browser does not support Service Workers');
            }
            
            // Test 2: Manifest
            try {
                const response = await fetch('manifest.json');
                if (response.ok) {
                    const manifest = await response.json();
                    addTestResult('Web App Manifest', 'success', `Manifest loaded successfully. App name: ${manifest.name}`);
                } else {
                    addTestResult('Web App Manifest', 'error', 'Failed to load manifest.json');
                }
            } catch (error) {
                addTestResult('Web App Manifest', 'error', 'Error loading manifest: ' + error.message);
            }
            
            // Test 3: HTTPS/Localhost
            const isSecure = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            addTestResult('Secure Context', isSecure ? 'success' : 'warning', 
                         isSecure ? 'Running in secure context (HTTPS/localhost)' : 'PWA features may be limited on non-HTTPS sites');
            
            // Test 4: Install Prompt Support
            if ('BeforeInstallPromptEvent' in window) {
                addTestResult('Install Prompt Support', 'success', 'Browser supports install prompts');
            } else {
                addTestResult('Install Prompt Support', 'warning', 'Browser has limited install prompt support');
            }
            
            // Test 5: PWA Installation Criteria
            const hasManifest = document.querySelector('link[rel="manifest"]') !== null;
            const hasServiceWorker = 'serviceWorker' in navigator;
            const isSecureContext = location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1';
            
            const installationReady = hasManifest && hasServiceWorker && isSecureContext;
            addTestResult('PWA Installation Ready', installationReady ? 'success' : 'warning', 
                         installationReady ? 'All PWA installation requirements met' : 'Some PWA requirements missing');
            
            // Test 6: Display Mode
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
            addTestResult('Display Mode', isStandalone ? 'success' : 'warning', 
                         isStandalone ? 'Running in standalone mode (installed PWA)' : 'Running in browser mode');
            
            // Test 7: Offline Detection
            addTestResult('Network Status', navigator.onLine ? 'success' : 'warning', 
                         navigator.onLine ? 'Currently online' : 'Currently offline');
            
            // Test 8: Notification Support
            if ('Notification' in window) {
                const permission = Notification.permission;
                addTestResult('Push Notifications', 
                             permission === 'granted' ? 'success' : 'warning', 
                             `Notification permission: ${permission}`);
            } else {
                addTestResult('Push Notifications', 'error', 'Browser does not support notifications');
            }
            
            // Summary
            const successCount = testResults.filter(t => t.status === 'success').length;
            const totalTests = testResults.length - 1; // Exclude the "Starting Tests" entry
            
            addTestResult('Test Summary', 'success', `Completed ${totalTests} tests. ${successCount} passed.`);
        }
        
        function testInstallPrompt() {
            alert('Install prompt test: Check the browser address bar for an install icon, or wait for the custom install prompt to appear on the main page.');
        }
        
        function testMobileInstall() {
            alert('Mobile Installation Test:\n\n1. On Android Chrome: Look for "Add to Home Screen" in the menu\n2. On iOS Safari: Tap Share button → "Add to Home Screen"\n3. The app will appear on your home screen like a native app');
        }
        
        function testStandaloneMode() {
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
            if (isStandalone) {
                alert('✅ App is running in standalone mode (installed as PWA)');
            } else {
                alert('ℹ️ App is running in browser mode. Install the app to test standalone mode.');
            }
        }
        
        // Run tests automatically when page loads
        window.addEventListener('load', runTests);
        
        // Listen for online/offline events
        window.addEventListener('online', () => {
            addTestResult('Network Status', 'success', 'Connection restored - back online');
        });
        
        window.addEventListener('offline', () => {
            addTestResult('Network Status', 'warning', 'Connection lost - now offline');
        });
    </script>
</body>
</html>