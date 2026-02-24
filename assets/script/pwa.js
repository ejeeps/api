/**
 * E-JEEP PWA Registration and Install Handler
 */

(function () {
    'use strict';

    let installPromptEvent = null;

    // Register Service Worker
    function registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/api/service-worker.js')
                    .then(function (registration) {
                        console.log('E-JEEP Service Worker registered:', registration.scope);
                    })
                    .catch(function (error) {
                        console.error('E-JEEP Service Worker registration failed:', error);
                    });
            });
        } else {
            console.log('Service Worker not supported in this browser');
        }
    }

    // Handle beforeinstallprompt event
    function handleInstallPrompt() {
        window.addEventListener('beforeinstallprompt', function (e) {
            // Prevent the mini-infobar from appearing on mobile
            e.preventDefault();
            // Store the event for later use
            installPromptEvent = e;
            console.log('E-JEEP PWA is ready to install');

            // Dispatch custom event so pages can show install button
            window.dispatchEvent(new CustomEvent('ejeep-pwa-installable'));
        });
    }

    // Trigger install prompt
    function promptInstall() {
        if (!installPromptEvent) {
            console.log('Install prompt not available');
            return Promise.resolve(false);
        }

        installPromptEvent.prompt();

        return installPromptEvent.userChoice.then(function (choiceResult) {
            if (choiceResult.outcome === 'accepted') {
                console.log('User accepted the E-JEEP PWA install');
            } else {
                console.log('User dismissed the E-JEEP PWA install');
            }
            installPromptEvent = null;
            return choiceResult.outcome === 'accepted';
        });
    }

    // Check if app is installed
    function isAppInstalled() {
        return window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true;
    }

    // Listen for app installed event
    function handleAppInstalled() {
        window.addEventListener('appinstalled', function () {
            console.log('E-JEEP PWA was installed');
            installPromptEvent = null;
            window.dispatchEvent(new CustomEvent('ejeep-pwa-installed'));
        });
    }

    // Initialize PWA features
    function init() {
        registerServiceWorker();
        handleInstallPrompt();
        handleAppInstalled();
    }

    // Expose public API
    window.EjeepPWA = {
        promptInstall: promptInstall,
        isAppInstalled: isAppInstalled,
        isInstallable: function () {
            return installPromptEvent !== null;
        }
    };

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
