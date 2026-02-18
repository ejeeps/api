<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWA Debug - E-JEEP</title>
    <link rel="manifest" href="manifest.json">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .debug-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-good { color: #22c55e; font-weight: bold; }
        .status-bad { color: #ef4444; font-weight: bold; }
        .status-warning { color: #f59e0b; font-weight: bold; }
        .standalone-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #22c55e;
            color: white;
            padding: 10px;
            border-radius: 5px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="standalone-indicator" id="standaloneIndicator">
        📱 Running as Standalone App!
    </div>
    
    <h1>🔧 PWA Debug Tool</h1>
    
    <div class="debug-card">
        <h3>Display Mode Detection</h3>
        <p id="displayMode">Checking...</p>
        <p id="standaloneStatus">Checking...</p>
    </div>
    
    <div class="debug-card">
        <h3>PWA Installation Status</h3>
        <p id="installStatus">Checking...</p>
        <button onclick="testInstall()" style="padding: 10px 20px; background: #22c55e; color: white; border: none; border-radius: 5px; cursor: pointer;">Test Install</button>
    </div>
    
    <div class="debug-card">
        <h3>Manifest Check</h3>
        <p id="manifestStatus">Checking...</p>
    </div>
    
    <div class="debug-card">
        <h3>Service Worker Status</h3>
        <p id="swStatus">Checking...</p>
    </div>
    
    <div class="debug-card">
        <h3>Quick Actions</h3>
        <button onclick="uninstallApp()" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">Uninstall App</button>
        <button onclick="window.location.href='index.php'" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px;">Back to Main</button>
    </div>

    <script>
        let deferredPrompt;
        
        // Check display mode
        function checkDisplayMode() {
            const displayModeEl = document.getElementById('displayMode');
            const standaloneEl = document.getElementById('standaloneStatus');
            const indicator = document.getElementById('standaloneIndicator');
            
            if (window.matchMedia('(display-mode: standalone)').matches) {
                displayModeEl.innerHTML = '<span class="status-good">✅ Standalone Mode</span> - App is running without browser UI';
                standaloneEl.innerHTML = '<span class="status-good">✅ Perfect!</span> The app is working as intended';
                indicator.style.display = 'block';
                document.body.style.background = '#e6fffa';
            } else if (window.navigator.standalone === true) {
                displayModeEl.innerHTML = '<span class="status-good">✅ iOS Standalone</span> - Running as web app on iOS';
                standaloneEl.innerHTML = '<span class="status-good">✅ Perfect!</span> iOS web app mode active';
                indicator.style.display = 'block';
            } else {
                displayModeEl.innerHTML = '<span class="status-warning">⚠️ Browser Mode</span> - Running in regular browser';
                standaloneEl.innerHTML = '<span class="status-bad">❌ Not Installed</span> - App needs to be installed to run standalone';
            }
        }
        
        // Check installation status
        function checkInstallStatus() {
            const installEl = document.getElementById('installStatus');
            
            if (window.matchMedia('(display-mode: standalone)').matches) {
                installEl.innerHTML = '<span class="status-good">✅ App is Installed</span> - Running in standalone mode';
            } else {
                installEl.innerHTML = '<span class="status-warning">⚠️ Not Installed</span> - App is running in browser mode';
            }
        }
        
        // Check manifest
        async function checkManifest() {
            const manifestEl = document.getElementById('manifestStatus');
            try {
                const response = await fetch('manifest.json');
                if (response.ok) {
                    const manifest = await response.json();
                    manifestEl.innerHTML = `<span class="status-good">✅ Manifest OK</span><br>
                        Name: ${manifest.name}<br>
                        Display: ${manifest.display}<br>
                        Start URL: ${manifest.start_url}<br>
                        Scope: ${manifest.scope}`;
                } else {
                    manifestEl.innerHTML = '<span class="status-bad">❌ Manifest Error</span> - Could not load manifest.json';
                }
            } catch (error) {
                manifestEl.innerHTML = `<span class="status-bad">❌ Manifest Error</span> - ${error.message}`;
            }
        }
        
        // Check service worker
        async function checkServiceWorker() {
            const swEl = document.getElementById('swStatus');
            
            if ('serviceWorker' in navigator) {
                try {
                    const registration = await navigator.serviceWorker.getRegistration();
                    if (registration) {
                        swEl.innerHTML = `<span class="status-good">✅ Service Worker Active</span><br>
                            Scope: ${registration.scope}<br>
                            State: ${registration.active ? registration.active.state : 'Unknown'}`;
                    } else {
                        swEl.innerHTML = '<span class="status-warning">⚠️ Service Worker Not Registered</span>';
                    }
                } catch (error) {
                    swEl.innerHTML = `<span class="status-bad">❌ Service Worker Error</span> - ${error.message}`;
                }
            } else {
                swEl.innerHTML = '<span class="status-bad">❌ Service Worker Not Supported</span>';
            }
        }
        
        // Test install
        function testInstall() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        alert('App installation accepted! Please wait and then refresh this page.');
                    } else {
                        alert('App installation dismissed.');
                    }
                    deferredPrompt = null;
                });
            } else {
                alert('Install prompt not available. Try:\n\n1. Chrome: Look for install icon in address bar\n2. Safari: Share → Add to Home Screen\n3. Or the app might already be installed');
            }
        }
        
        // Uninstall app (for testing)
        function uninstallApp() {
            alert('To uninstall:\n\n1. Android: Long press app icon → Uninstall\n2. iOS: Long press app icon → Remove App\n3. Or remove from browser settings');
        }
        
        // Listen for install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            console.log('Install prompt available');
        });
        
        // Run checks when page loads
        window.addEventListener('load', () => {
            checkDisplayMode();
            checkInstallStatus();
            checkManifest();
            checkServiceWorker();
        });
        
        // Listen for display mode changes
        window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
            checkDisplayMode();
            checkInstallStatus();
        });
    </script>
</body>
</html>