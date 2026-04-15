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

    /* ── Trips today: route map modal (Leaflet + OSRM) ───────────────────── */
    var routeMapInstance = null;

    function parseRouteGeoFromCard(card) {
        var raw = card.getAttribute('data-route-geo');
        if (!raw) return null;
        try {
            var o = JSON.parse(raw);
            if (!o || typeof o !== 'object') return null;
            var slat = o.startLat;
            var slng = o.startLng;
            var elat = o.endLat;
            var elng = o.endLng;
            if (slat == null || slng == null || elat == null || elng == null) return null;
            slat = parseFloat(slat);
            slng = parseFloat(slng);
            elat = parseFloat(elat);
            elng = parseFloat(elng);
            if ([slat, slng, elat, elng].some(function (n) { return Number.isNaN(n); })) return null;
            return { startLat: slat, startLng: slng, endLat: elat, endLng: elng };
        } catch (e) {
            return null;
        }
    }

    function destroyRouteMap() {
        if (routeMapInstance) {
            try {
                routeMapInstance.remove();
            } catch (e) {
                /* ignore */
            }
            routeMapInstance = null;
        }
    }

    function openRouteMapModal(card) {
        var modal = document.getElementById('routeMapModal');
        var mapEl = document.getElementById('routeMapModalMap');
        var titleEl = document.getElementById('routeMapModalTitle');
        var errEl = document.getElementById('routeMapModalError');
        var loadingEl = document.getElementById('routeMapModalLoading');
        if (!modal || !mapEl) return;

        var from = card.getAttribute('data-route-from') || '';
        var to = card.getAttribute('data-route-to') || '';
        if (titleEl) {
            titleEl.textContent = from && to ? from + ' → ' + to : 'Route';
        }
        if (errEl) {
            errEl.hidden = true;
            errEl.textContent = '';
        }
        if (loadingEl) {
            loadingEl.hidden = false;
        }

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';

        destroyRouteMap();
        mapEl.innerHTML = '';

        if (typeof L === 'undefined') {
            if (loadingEl) loadingEl.hidden = true;
            if (errEl) {
                errEl.textContent = 'Map could not load. Check your connection, allow this page to load scripts from unpkg.com, then refresh.';
                errEl.hidden = false;
            }
            return;
        }

        var coords = parseRouteGeoFromCard(card);
        if (!coords) {
            if (loadingEl) loadingEl.hidden = true;
            if (errEl) {
                errEl.textContent = 'No start/end points for this route yet. Your operator can set them on the route, or they appear from today’s trip GPS once taps are logged.';
                errEl.hidden = false;
            }
            return;
        }

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                buildRouteMap(coords, mapEl, loadingEl, errEl);
            });
        });
    }

    function buildRouteMap(coords, mapEl, loadingEl, errEl) {
        routeMapInstance = L.map(mapEl, { scrollWheelZoom: true });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(routeMapInstance);

        var layerGroup = L.layerGroup().addTo(routeMapInstance);

        var url = 'https://router.project-osrm.org/route/v1/driving/' +
            coords.startLng + ',' + coords.startLat + ';' +
            coords.endLng + ',' + coords.endLat +
            '?geometries=geojson&overview=full';

        fetch(url)
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (loadingEl) loadingEl.hidden = true;
                var ok = data && (data.code === 'Ok' || data.code === 0);
                var route = ok && data.routes && data.routes[0];
                if (!route || !route.geometry) {
                    throw new Error('no route');
                }
                var gjLayer = L.geoJSON(route.geometry, {
                    style: { color: '#16a34a', weight: 5, opacity: 0.92 }
                }).addTo(layerGroup);

                var startIcon = L.divIcon({
                    className: 'route-map-marker route-map-marker--start',
                    html: '<span class="route-map-marker__dot"></span>',
                    iconSize: [18, 18],
                    iconAnchor: [9, 9]
                });
                var endIcon = L.divIcon({
                    className: 'route-map-marker route-map-marker--end',
                    html: '<span class="route-map-marker__dot"></span>',
                    iconSize: [18, 18],
                    iconAnchor: [9, 9]
                });
                L.marker([coords.startLat, coords.startLng], { icon: startIcon }).addTo(layerGroup);
                L.marker([coords.endLat, coords.endLng], { icon: endIcon }).addTo(layerGroup);

                routeMapInstance.fitBounds(gjLayer.getBounds(), { padding: [40, 40] });
                routeMapInstance.invalidateSize();
            })
            .catch(function () {
                if (loadingEl) loadingEl.hidden = true;
                if (errEl) {
                    errEl.textContent = 'Could not load the road route from OSRM. Showing a straight line between start and end.';
                    errEl.hidden = false;
                }
                var latlngs = [[coords.startLat, coords.startLng], [coords.endLat, coords.endLng]];
                L.polyline(latlngs, { color: '#16a34a', weight: 4, dashArray: '10 8', opacity: 0.9 }).addTo(layerGroup);
                L.circleMarker([coords.startLat, coords.startLng], {
                    radius: 7,
                    color: '#15803d',
                    fillColor: '#22c55e',
                    fillOpacity: 1,
                    weight: 2
                }).addTo(layerGroup);
                L.circleMarker([coords.endLat, coords.endLng], {
                    radius: 7,
                    color: '#b91c1c',
                    fillColor: '#ef4444',
                    fillOpacity: 1,
                    weight: 2
                }).addTo(layerGroup);
                routeMapInstance.fitBounds(L.latLngBounds(latlngs), { padding: [48, 48] });
                routeMapInstance.invalidateSize();
            });
    }

    function closeRouteMapModal() {
        var modal = document.getElementById('routeMapModal');
        if (modal) {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        }
        document.body.style.overflow = '';
        destroyRouteMap();
        var mapEl = document.getElementById('routeMapModalMap');
        if (mapEl) mapEl.innerHTML = '';
    }

    function initTripsTodayRouteMap() {
        document.querySelectorAll('.trips-today-card--clickable').forEach(function (card) {
            card.addEventListener('click', function () {
                openRouteMapModal(card);
            });
            card.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openRouteMapModal(card);
                }
            });
        });
        var modal = document.getElementById('routeMapModal');
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeRouteMapModal();
            });
        }
        var closeBtn = document.getElementById('routeMapModalClose');
        if (closeBtn) closeBtn.addEventListener('click', closeRouteMapModal);
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            var m = document.getElementById('routeMapModal');
            if (m && m.classList.contains('open')) closeRouteMapModal();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTripsTodayRouteMap);
    } else {
        initTripsTodayRouteMap();
    }

    window.closeRouteMapModal = closeRouteMapModal;
})();