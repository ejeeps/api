(function() {
    'use strict';

    function flipIdCard() {
        const flipCardInner = document.getElementById('flipCardInner');
        if (flipCardInner) {
            flipCardInner.classList.toggle('flipped');
        }
    }

    function viewFullscreen(imageId) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        const img = document.getElementById(imageId);

        if (img && modal && modalImg) {
            modal.style.display = 'block';
            modalImg.src = img.src;
            modalImg.alt = img.alt;
        }
    }

    function closeFullscreen() {
        const modal = document.getElementById('imageModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function initModal() {
        const modalClose = document.querySelector('.modal-close');
        if (modalClose) {
            modalClose.addEventListener('click', closeFullscreen);
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeFullscreen();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModal);
    } else {
        initModal();
    }

    window.flipIdCard = flipIdCard;
    window.viewFullscreen = viewFullscreen;
    window.closeFullscreen = closeFullscreen;

    function flipVirtualEjeepCard(event) {
        const flipCard = event && event.currentTarget ? event.currentTarget : document.querySelector('.ejeep-flip-card');
        if (!flipCard) return;
        flipCard.classList.toggle('flipped');
    }

    async function copyTextToClipboard(text) {
        if (!text) return false;

        try {
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                await navigator.clipboard.writeText(text);
                return true;
            }
        } catch (e) {
            // fall back
        }

        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        let ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (e) {
            ok = false;
        }
        document.body.removeChild(textarea);
        return ok;
    }

    function copyVirtualCardNumber(event) {
        const btn = event && event.currentTarget ? event.currentTarget : null;
        const card = btn ? btn.closest('.ejeep-flip-card') : null;
        if (!card) return;

        const raw = card.getAttribute('data-card-number-raw') || '';
        const trimmed = raw.trim();
        if (!trimmed) return;

        const originalTitle = btn.getAttribute('title') || 'Copy card number';
        btn.setAttribute('title', 'Copied!');
        btn.setAttribute('aria-label', 'Copied!');

        copyTextToClipboard(trimmed).then(function(success) {
            setTimeout(function() {
                btn.setAttribute('title', originalTitle);
                btn.setAttribute('aria-label', 'Copy card number');
            }, 1200);
        }).catch(function() {
            setTimeout(function() {
                btn.setAttribute('title', originalTitle);
                btn.setAttribute('aria-label', 'Copy card number');
            }, 1200);
        });
    }

    function toggleVirtualBalanceVisibility(event) {
        const btn = event && event.currentTarget ? event.currentTarget : null;
        const card = btn ? btn.closest('.ejeep-flip-card') : null;
        if (!card) return;

        const isVisible = card.getAttribute('data-balance-visible') !== 'false';
        card.setAttribute('data-balance-visible', isVisible ? 'false' : 'true');

        btn.setAttribute('aria-label', isVisible ? 'Show balance' : 'Hide balance');
        btn.setAttribute('title', isVisible ? 'Show balance' : 'Hide balance');
    }

    window.flipVirtualEjeepCard = flipVirtualEjeepCard;
    window.copyVirtualCardNumber = copyVirtualCardNumber;
    window.toggleVirtualBalanceVisibility = toggleVirtualBalanceVisibility;
})();