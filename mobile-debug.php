<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile PWA Debug</title>
    <link rel="manifest" href="manifest.json">
    
    <!-- PWA Meta Tags -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="E-JEEP">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            line-height: 1.6;
        }
        .debug-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-good { color: #22c55e; font-weight: bold; }
        .status-bad { color: #ef4444; font-weight: bold; }
        .status-warning { color: #f59e0b; font-weight: bold; }
        .standalone-indicator {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: #22c55e;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            z-index: 1000;
            display: none;
        }
        .device-info {
            font-size: 0.9rem;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .fix-button {
            background: #22c55e;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            margin: 5px;
            font-size: 0.9rem;
        }
        .fix-button:hover {
            background: #16a34a;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        h3 {
            color: #333;
            border-bottom: 2px solid #22c55e;
            padding-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="standalone-indicator" id="standaloneIndicator">
        📱 Perfect! Running in Standalone Mode
    </div>
    
    <h1>📱 Mobile PWA Debug Tool</h1>
    
    <div class="debug-card">
        <h3>🔍 Display Mode Detection</h3>
        <p id="displayMode">Checking...</p>
        <p id="standaloneStatus">Checking...</p>
        <div class="device-info" id="deviceInfo"></div>
    </div>
    
    <div class="debug-card">
        <h3>📱 Mobile Installation Status</h3>
        <p id="installStatus">Checking...</p>
        <p id="browserInfo">Checking...</p>
    </div>
    
    <div class="debug-card">
        <h3>🔧 Quick Fixes</h3>
        <p><strong>If you still see browser interface on mobile:</strong></p>
        <button class="fix-button" onclick="showInstallInstructions()">📱 Show Install Instructions</button>
        <button class="fix-button" onclick="testStandalone()">🧪 Test Standalone Mode</button>
        <button class="fix-button" onclick="clearAndReinstall()">🔄 Clear & Reinstall Guide</button>
    </div>
    
    <div class="debug-card">
        <h3>📋 Installation Checklist</h3>
        <div id="checklist">
            <p>✅ <strong>Step 1:</strong> Uninstall current PWA (if installed)</p>
            <p>✅ <strong>Step 2:</strong> Clear browser cache completely</p>
            <p>✅ <strong>Step 3:</strong> Visit main page and use install button</p>
            <p>✅ <strong>Step 4:</strong> Confirm installation in browser dialog</p>
            <p>✅ <strong>Step 5:</strong> Launch app from home screen (not browser)</p>
        </div>
    </div>
    
    <div class="debug-card">
        <h3>🏠 Navigation</h3>
        <button class="fix-button" onclick="window.location.href='index.php'">← Back to Main Page</button>
        <button class="fix-button" onclick="window.location.href='pwa-debug.php'">🔧 Full PWA Debug</button>
    </div>

    <script>
        // Check display mode and device info
        function checkDisplayMode() {
            const displayModeEl = document.getElementById('displayMode');
            const standaloneEl = document.getElementById('standaloneStatus');
            const deviceInfoEl = document.getElementById('deviceInfo');
            const indicator = document.getElementById('standaloneIndicator');
            
            // Get device info
            const userAgent = navigator.userAgent;
            const isMobile = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent);
            const isAndroid = /Android/i.test(userAgent);
            const isIOS = /iPhone|iPad|iPod/i.test(userAgent);
            const isChrome = /Chrome/i.test(userAgent);
            const isSafari = /Safari/i.test(userAgent) && !isChrome;
            
            deviceInfoEl.innerHTML = `
                <strong>Device:</strong> ${isMobile ? 'Mobile' : 'Desktop'}<br>
                <strong>Platform:</strong> ${isAndroid ? 'Android' : isIOS ? 'iOS' : 'Other'}<br>
                <strong>Browser:</strong> ${isChrome ? 'Chrome' : isSafari ? 'Safari' : 'Other'}<br>
                <strong>Screen:</strong> ${window.innerWidth}x${window.innerHeight}
            `;
            
            // Check standalone mode
            if (window.matchMedia('(display-mode: standalone)').matches) {
                displayModeEl.innerHTML = '<span class="status-good">✅ Perfect! Standalone Mode</span> - No browser interface';
                standaloneEl.innerHTML = '<span class="status-good">🎉 SUCCESS!</span> Your PWA is working correctly on mobile';
                indicator.style.display = 'block';
                document.body.style.background = '#e6fffa';
            } else if (window.navigator.standalone === true) {
                displayModeEl.innerHTML = '<span class="status-good">✅ iOS Standalone</span> - Running as web app';
                standaloneEl.innerHTML = '<span class="status-good">🎉 SUCCESS!</span> iOS web app mode active';
                indicator.style.display = 'block';
            } else {
                displayModeEl.innerHTML = '<span class="status-bad">❌ Browser Mode</span> - Still showing browser interface';
                if (isMobile) {
                    standaloneEl.innerHTML = '<span class="status-bad">🔧 NEEDS FIX!</span> PWA not installed correctly on mobile';
                } else {
                    standaloneEl.innerHTML = '<span class="status-warning">ℹ️ Desktop Browser</span> - This is normal for desktop';
                }
            }
        }
        
        // Check installation status
        function checkInstallStatus() {
            const installEl = document.getElementById('installStatus');
            const browserEl = document.getElementById('browserInfo');
            
            const userAgent = navigator.userAgent.toLowerCase();
            const isMobile = /android|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent);
            
            if (window.matchMedia('(display-mode: standalone)').matches) {
                installEl.innerHTML = '<span class="status-good">✅ PWA Installed Correctly</span>';
                browserEl.innerHTML = '<span class="status-good">Perfect!</span> App is running without browser interface';
            } else if (isMobile) {
                installEl.innerHTML = '<span class="status-bad">❌ PWA Not Properly Installed</span>';
                if (userAgent.includes('chrome') && userAgent.includes('android')) {
                    browserEl.innerHTML = '<span class="status-warning">Android Chrome:</span> Use install button or address bar install icon';
                } else if (userAgent.includes('safari') && (userAgent.includes('iphone') || userAgent.includes('ipad'))) {
                    browserEl.innerHTML = '<span class="status-warning">iOS Safari:</span> Use Share → Add to Home Screen';
                } else {
                    browserEl.innerHTML = '<span class="status-warning">Mobile Browser:</span> Check if PWA installation is supported';
                }
            } else {
                installEl.innerHTML = '<span class="status-good">✅ Desktop Mode</span>';
                browserEl.innerHTML = 'Desktop browsers may show some browser interface - this is normal';
            }
        }
        
        // Show install instructions
        function showInstallInstructions() {
            const userAgent = navigator.userAgent.toLowerCase();
            let instructions = '';
            
            if (userAgent.includes('chrome') && userAgent.includes('android')) {
                instructions = `📱 Android Chrome Installation:
                
1. 🗑️ First, uninstall current app (if installed)
2. 🧹 Clear browser cache (Settings → Privacy → Clear browsing data)
3. 🏠 Go to main E-JEEP page
4. 👆 Tap install button OR look for install icon (⬇️) in address bar
5. ✅ Confirm installation
6. 🚀 Launch app from HOME SCREEN (not browser bookmarks)

Important: Always launch the app from your phone's home screen, not from browser!`;
            } else if (userAgent.includes('safari') && (userAgent.includes('iphone') || userAgent.includes('ipad'))) {
                instructions = `🍎 iPhone/iPad Safari Installation:
                
1. 🗑️ Remove current app from home screen (if exists)
2. 🧹 Clear Safari cache (Settings → Safari → Clear History and Website Data)
3. 🏠 Go to main E-JEEP page in Safari
4. 📤 Tap Share button (bottom of screen)
5. ➕ Select "Add to Home Screen"
6. ✏️ Edit name if needed, tap "Add"
7. 🚀 Launch app from HOME SCREEN

Important: Must use Safari browser, not Chrome on iOS!`;
            } else {
                instructions = `📱 General Mobile Installation:
                
1. Use Chrome on Android or Safari on iPhone
2. Uninstall any existing version
3. Clear browser cache completely
4. Visit E-JEEP main page
5. Use browser's "Add to Home Screen" feature
6. Always launch from home screen, not browser`;
            }
            
            alert(instructions);
        }
        
        // Test standalone mode
        function testStandalone() {
            if (window.matchMedia('(display-mode: standalone)').matches) {
                alert('✅ SUCCESS! Your app is running in standalone mode.\n\nThis means:\n• No browser address bar\n• No browser navigation\n• Full screen app experience\n• Looks like a native app');
            } else {
                alert('❌ NOT STANDALONE\n\nYour app is still running in browser mode.\n\nTo fix:\n1. Uninstall current app\n2. Clear browser cache\n3. Reinstall using proper method\n4. Launch from HOME SCREEN only');
            }
        }
        
        // Clear and reinstall guide
        function clearAndReinstall() {
            const guide = `🔄 Complete Clear & Reinstall Guide:

STEP 1 - Remove Current Installation:
• Android: Long press app icon → Uninstall
• iPhone: Long press app icon → Remove App

STEP 2 - Clear Browser Cache:
• Android Chrome: Settings → Privacy → Clear browsing data → All time
• iPhone Safari: Settings → Safari → Clear History and Website Data

STEP 3 - Fresh Installation:
• Visit: E-JEEP main page
• Use install button or browser's install option
• Confirm installation when prompted

STEP 4 - Proper Launch:
• ALWAYS launch from home screen
• NEVER launch from browser bookmarks
• App should open without browser interface

If still showing browser interface, repeat all steps!`;
            
            alert(guide);
        }
        
        // Run checks when page loads
        window.addEventListener('load', () => {
            checkDisplayMode();
            checkInstallStatus();
        });
        
        // Listen for display mode changes
        window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
            checkDisplayMode();
            checkInstallStatus();
        });
    </script>
</body>
</html>