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
})();





















