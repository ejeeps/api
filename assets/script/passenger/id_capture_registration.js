/**
 * ID photo capture via device camera (getUserMedia), with gallery fallback.
 * Fills hidden file inputs for passenger registration step 4.
 */
(function () {
    'use strict';

    function emptyFileList(input) {
        input.value = '';
        input.files = new DataTransfer().files;
    }

    function setFile(input, blob, filename) {
        var file = new File([blob], filename, { type: blob.type || 'image/jpeg' });
        var dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function stopStream(stream) {
        if (!stream) {
            return;
        }
        stream.getTracks().forEach(function (t) {
            t.stop();
        });
    }

    function openCamera() {
        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            return Promise.reject(new Error('no-api'));
        }
        var constraints = {
            audio: false,
            video: { facingMode: { ideal: 'environment' } },
        };
        return navigator.mediaDevices.getUserMedia(constraints).catch(function () {
            return navigator.mediaDevices.getUserMedia({ audio: false, video: true });
        });
    }

    function initBlock(block) {
        var targetId = block.getAttribute('data-target');
        var fileInput = targetId ? document.getElementById(targetId) : block.querySelector('input[type="file"]');
        if (!fileInput) {
            return;
        }

        var viewport = block.querySelector('.id-capture-viewport');
        var placeholder = block.querySelector('.id-capture-placeholder');
        var video = block.querySelector('.id-capture-video');
        var preview = block.querySelector('.id-capture-preview');
        var btnStart = block.querySelector('.id-capture-start');
        var btnSnap = block.querySelector('.id-capture-snap');
        var btnRetake = block.querySelector('.id-capture-retake');
        var btnPick = block.querySelector('.id-capture-pick');
        if (!video || !preview || !btnStart || !btnSnap || !btnRetake || !btnPick) {
            return;
        }

        function setPlaceholderVisible(visible) {
            if (!placeholder) {
                return;
            }
            placeholder.hidden = !visible;
            placeholder.setAttribute('aria-hidden', visible ? 'false' : 'true');
            if (viewport) {
                viewport.classList.toggle('id-capture-viewport--has-media', !visible);
            }
        }

        var stream = null;
        var previewUrl = null;

        function revokePreview() {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }
            preview.removeAttribute('src');
        }

        function showLive() {
            revokePreview();
            preview.hidden = true;
            video.hidden = false;
            setPlaceholderVisible(false);
        }

        function showPreviewFromFile(file) {
            revokePreview();
            previewUrl = URL.createObjectURL(file);
            preview.src = previewUrl;
            preview.hidden = false;
            video.hidden = true;
            setPlaceholderVisible(false);
        }

        function resetToIdle() {
            stopStream(stream);
            stream = null;
            video.srcObject = null;
            revokePreview();
            preview.hidden = true;
            video.hidden = true;
            emptyFileList(fileInput);
            btnStart.hidden = false;
            btnSnap.hidden = true;
            btnRetake.hidden = true;
            setPlaceholderVisible(true);
        }

        btnStart.addEventListener('click', function () {
            openCamera()
                .then(function (s) {
                    stopStream(stream);
                    stream = s;
                    video.srcObject = s;
                    showLive();
                    btnStart.hidden = true;
                    btnSnap.hidden = false;
                    btnRetake.hidden = false;
                    return video.play();
                })
                .catch(function () {
                    window.alert(
                        'Could not open the camera. Allow camera access in your browser, use HTTPS or localhost, or tap Gallery to pick a photo.'
                    );
                });
        });

        btnSnap.addEventListener('click', function () {
            if (!stream || !video.videoWidth) {
                return;
            }
            var w = video.videoWidth;
            var h = video.videoHeight;
            var maxDim = 1920;
            var scale = Math.min(1, maxDim / Math.max(w, h));
            var cw = Math.round(w * scale);
            var ch = Math.round(h * scale);
            var canvas = document.createElement('canvas');
            canvas.width = cw;
            canvas.height = ch;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, cw, ch);
            canvas.toBlob(
                function (blob) {
                    if (!blob) {
                        return;
                    }
                    stopStream(stream);
                    stream = null;
                    video.srcObject = null;
                    setFile(fileInput, blob, 'id-' + fileInput.id + '.jpg');
                    showPreviewFromFile(fileInput.files[0]);
                    btnSnap.hidden = true;
                    btnStart.hidden = false;
                    btnRetake.hidden = false;
                    video.hidden = true;
                },
                'image/jpeg',
                0.92
            );
        });

        btnRetake.addEventListener('click', function () {
            resetToIdle();
        });

        btnPick.addEventListener('click', function () {
            fileInput.click();
        });

        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files[0]) {
                stopStream(stream);
                stream = null;
                video.srcObject = null;
                showPreviewFromFile(fileInput.files[0]);
                btnSnap.hidden = true;
                btnStart.hidden = false;
                btnRetake.hidden = false;
                video.hidden = true;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.id-capture-block').forEach(initBlock);
    });
})();
