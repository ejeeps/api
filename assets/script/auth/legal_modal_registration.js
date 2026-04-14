/**
 * Opens Terms / Privacy in an on-page modal (iframe) on registration forms.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('legalModal');
        if (!modal) return;

        var titleEl = document.getElementById('legalModalTitle');
        var frame = modal.querySelector('.legal-modal-frame');
        var closeBtns = modal.querySelectorAll('[data-legal-modal-close]');
        var triggers = document.querySelectorAll('.legal-modal-trigger');
        var lastFocus = null;

        function openModal(src, title) {
            if (!frame || !titleEl) return;
            lastFocus = document.activeElement;
            titleEl.textContent = title || 'Document';
            frame.setAttribute('title', title || 'Legal document');
            frame.src = src;
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            var closeBtn = modal.querySelector('.legal-modal-close');
            if (closeBtn) closeBtn.focus();
        }

        function closeModal() {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            frame.src = 'about:blank';
            document.body.style.overflow = '';
            if (lastFocus && typeof lastFocus.focus === 'function') {
                lastFocus.focus();
            }
        }

        triggers.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var src = btn.getAttribute('data-legal-src');
                var title = btn.getAttribute('data-legal-title') || '';
                if (src) openModal(src, title);
            });
        });

        closeBtns.forEach(function (el) {
            el.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    });
})();
