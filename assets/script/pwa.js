/**
 * E-JEEP PWA Registration and Install Handler
 */

(function () {
    'use strict';

    let installPromptEvent = null;
    let hasRefreshedForNewWorker = false;

    // Register Service Worker
    function registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/api/service-worker.js', {
                    // Ensure the browser does not serve stale SW script from HTTP cache.
                    updateViaCache: 'none'
                })
                    .then(function (registration) {
                        console.log('E-JEEP Service Worker registered:', registration.scope);
                        listenForServiceWorkerUpdates(registration);

                        // Check for updates immediately and periodically while app is open.
                        registration.update().catch(function (error) {
                            console.error('Initial Service Worker update check failed:', error);
                        });

                        setInterval(function () {
                            registration.update().catch(function (error) {
                                console.error('Periodic Service Worker update check failed:', error);
                            });
                        }, 5 * 60 * 1000);
                    })
                    .catch(function (error) {
                        console.error('E-JEEP Service Worker registration failed:', error);
                    });
            });
        } else {
            console.log('Service Worker not supported in this browser');
        }
    }

    function requestWaitingServiceWorkerActivation(registration) {
        if (registration.waiting) {
            registration.waiting.postMessage({ type: 'SKIP_WAITING' });
        }
    }

    function listenForServiceWorkerUpdates(registration) {
        // If an updated worker is already waiting, activate it now.
        requestWaitingServiceWorkerActivation(registration);

        registration.addEventListener('updatefound', function () {
            const newWorker = registration.installing;
            if (!newWorker) {
                return;
            }

            newWorker.addEventListener('statechange', function () {
                // Trigger activation only when there is an existing controller
                // (means this is an update, not first install).
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    requestWaitingServiceWorkerActivation(registration);
                }
            });
        });

        // Force a reload once the new worker controls this page.
        navigator.serviceWorker.addEventListener('controllerchange', function () {
            if (hasRefreshedForNewWorker) {
                return;
            }
            hasRefreshedForNewWorker = true;
            window.location.reload();
        });

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                registration.update().catch(function (error) {
                    console.error('Visibility Service Worker update check failed:', error);
                });
            }
        });
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
