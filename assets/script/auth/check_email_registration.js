/**
 * Live email availability for passenger/driver registration.
 * Expects the form to have data-email-check-url pointing to CheckEmailController.php.
 */
(function () {
    'use strict';

    var lastResult = { email: '', taken: null, at: 0 };
    var debounceTimer = null;

    function getForm() {
        return document.querySelector('form[data-email-check-url]');
    }

    function getEndpoint() {
        var f = getForm();
        return f ? f.getAttribute('data-email-check-url') : '';
    }

    function getEmailInput() {
        return document.getElementById('email');
    }

    function getMsgEl() {
        return document.getElementById('emailAvailabilityMsg');
    }

    function basicEmailValid(value) {
        if (!value || value.indexOf('@') < 1 || value.indexOf('.') < 0) {
            return false;
        }
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());
    }

    function setUiState(emailInput, state, message) {
        var msg = getMsgEl();
        if (msg) {
            msg.textContent = message || '';
            msg.hidden = !message;
            msg.classList.toggle('email-availability-msg--error', state === 'taken' || state === 'error');
        }
        if (!emailInput) {
            return;
        }
        if (state === 'taken' || state === 'error') {
            emailInput.classList.add('error');
            emailInput.setAttribute('aria-invalid', 'true');
            emailInput.setAttribute('data-email-status', state);
        } else if (state === 'available') {
            emailInput.classList.remove('error');
            emailInput.setAttribute('aria-invalid', 'false');
            emailInput.setAttribute('data-email-status', 'available');
        } else if (state === 'checking') {
            emailInput.setAttribute('data-email-status', 'checking');
        } else {
            emailInput.classList.remove('error');
            emailInput.removeAttribute('aria-invalid');
            emailInput.removeAttribute('data-email-status');
        }
    }

    function applyResponse(emailInput, data) {
        if (!data || !data.ok) {
            setUiState(emailInput, 'error', data && data.message ? data.message : 'Could not verify email.');
            lastResult = { email: emailInput ? emailInput.value.trim() : '', taken: null, at: Date.now() };
            return false;
        }
        if (data.taken) {
            setUiState(emailInput, 'taken', data.message || 'This email is already registered.');
            lastResult = { email: emailInput ? emailInput.value.trim() : '', taken: true, at: Date.now() };
            return false;
        }
        setUiState(emailInput, 'available', '');
        lastResult = { email: emailInput ? emailInput.value.trim() : '', taken: false, at: Date.now() };
        return true;
    }

    function fetchStatus(emailInput, forceRefresh) {
        var endpoint = getEndpoint();
        if (!endpoint || !emailInput) {
            return Promise.resolve(true);
        }
        var raw = emailInput.value.trim();
        if (!raw) {
            setUiState(emailInput, 'idle', '');
            return Promise.resolve(true);
        }
        if (!basicEmailValid(raw)) {
            setUiState(emailInput, 'idle', '');
            return Promise.resolve(true);
        }

        var now = Date.now();
        if (
            !forceRefresh &&
            lastResult.email === raw &&
            lastResult.taken !== null &&
            now - lastResult.at < 4000
        ) {
            return Promise.resolve(!lastResult.taken);
        }

        setUiState(emailInput, 'checking', 'Checking…');

        var url = endpoint + (endpoint.indexOf('?') >= 0 ? '&' : '?') + 'email=' + encodeURIComponent(raw);
        return fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                return applyResponse(emailInput, data);
            })
            .catch(function () {
                setUiState(emailInput, 'error', 'Could not verify email. Check your connection.');
                return false;
            });
    }

    function scheduleCheck(emailInput) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            debounceTimer = null;
            var raw = emailInput.value.trim();
            if (!raw || !basicEmailValid(raw)) {
                setUiState(emailInput, 'idle', '');
                lastResult = { email: '', taken: null, at: 0 };
                return;
            }
            fetchStatus(emailInput);
        }, 450);
    }

    /**
     * Call before Next (step 1): ensures server says email is free (always re-fetches).
     */
    window.ensureRegistrationEmailAvailable = function (emailInput) {
        if (!emailInput) {
            return Promise.resolve(true);
        }
        var raw = emailInput.value.trim();
        if (!raw) {
            return Promise.resolve(true);
        }
        if (!basicEmailValid(raw)) {
            return Promise.resolve(true);
        }
        return fetchStatus(emailInput, true);
    };

    document.addEventListener('DOMContentLoaded', function () {
        var form = getForm();
        var emailInput = getEmailInput();
        var endpoint = getEndpoint();
        if (!form || !emailInput || !endpoint) {
            return;
        }

        emailInput.addEventListener('blur', function () {
            var raw = emailInput.value.trim();
            if (!raw || !basicEmailValid(raw)) {
                setUiState(emailInput, 'idle', '');
                return;
            }
            fetchStatus(emailInput);
        });

        emailInput.addEventListener('input', function () {
            lastResult = { email: '', taken: null, at: 0 };
            setUiState(emailInput, 'idle', '');
            scheduleCheck(emailInput);
        });
    });
})();
