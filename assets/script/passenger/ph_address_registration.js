/**
 * Philippines-only address helpers: PSGC (online), OpenStreetMap Nominatim (online),
 * ph_provinces / ph_cities (offline) for passenger registration step 3.
 */
(function () {
    'use strict';

    function getForm() {
        return document.getElementById('passengerRegistrationForm');
    }

    function apiUrl(action, params) {
        var form = getForm();
        if (!form) {
            return '';
        }
        var base = form.getAttribute('data-ph-address-url');
        if (!base) {
            return '';
        }
        var q = 'action=' + encodeURIComponent(action);
        if (params) {
            Object.keys(params).forEach(function (k) {
                var v = params[k];
                if (v != null && v !== '') {
                    q += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(String(v));
                }
            });
        }
        return base + (base.indexOf('?') >= 0 ? '&' : '?') + q;
    }

    function norm(s) {
        return String(s || '')
            .trim()
            .toLowerCase();
    }

    function bestProvinceOption(select, nameFromGeo) {
        var want = norm(nameFromGeo);
        if (!want) {
            return null;
        }
        var i;
        var opt;
        var v;
        for (i = 0; i < select.options.length; i++) {
            opt = select.options[i];
            v = norm(opt.value);
            if (v && v === want) {
                return opt;
            }
        }
        for (i = 0; i < select.options.length; i++) {
            opt = select.options[i];
            v = norm(opt.value);
            if (v && (v.indexOf(want) !== -1 || want.indexOf(v) !== -1)) {
                return opt;
            }
        }
        if (want.indexOf('metro') !== -1) {
            for (i = 0; i < select.options.length; i++) {
                opt = select.options[i];
                if (norm(opt.value).indexOf('metro') !== -1) {
                    return opt;
                }
            }
        }
        return null;
    }

    function matchCityOption(citySel, cityName) {
        var cWant = norm(cityName);
        if (!cWant) {
            return false;
        }
        var j;
        var cv;
        for (j = 0; j < citySel.options.length; j++) {
            cv = norm(citySel.options[j].value);
            if (cv && cv === cWant) {
                citySel.selectedIndex = j;
                return true;
            }
        }
        for (j = 0; j < citySel.options.length; j++) {
            cv = norm(citySel.options[j].value);
            if (cv && (cv.indexOf(cWant) !== -1 || cWant.indexOf(cv) !== -1)) {
                citySel.selectedIndex = j;
                return true;
            }
        }
        return false;
    }

    function addCityOptionIfMissing(citySel, cityName) {
        if (!cityName || matchCityOption(citySel, cityName)) {
            return;
        }
        var o = document.createElement('option');
        o.value = cityName;
        o.textContent = cityName;
        citySel.appendChild(o);
        citySel.value = cityName;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var form = getForm();
        if (!form || !form.getAttribute('data-ph-address-url')) {
            return;
        }

        var provinceSel = document.getElementById('province');
        var citySel = document.getElementById('city');
        var searchInput = document.getElementById('ph_address_search');
        var suggList = document.getElementById('ph_address_suggestions');
        var hintEl = document.getElementById('ph_address_source_hint');
        var wrap = document.querySelector('.address-search-wrap');

        if (!provinceSel || !citySel || !searchInput || !suggList) {
            return;
        }

        var debounceTimer = null;

        function setHint(text) {
            if (hintEl) {
                hintEl.textContent = text;
            }
        }

        function hideSugg() {
            suggList.hidden = true;
            suggList.innerHTML = '';
        }

        function showSugg() {
            suggList.hidden = false;
        }

        function fillCitiesFromData(data) {
            var cities = data.cities || [];
            var frag = document.createDocumentFragment();
            var o0 = document.createElement('option');
            o0.value = '';
            o0.textContent = cities.length ? 'Select city / municipality' : 'No list — try search above or type later';
            frag.appendChild(o0);
            cities.forEach(function (c) {
                var o = document.createElement('option');
                o.value = c.name;
                o.textContent = c.name;
                frag.appendChild(o);
            });
            citySel.innerHTML = '';
            citySel.appendChild(frag);
        }

        function loadCities(provinceCode) {
            citySel.disabled = true;
            citySel.innerHTML = '';
            var loading = document.createElement('option');
            loading.value = '';
            loading.textContent = 'Loading…';
            citySel.appendChild(loading);

            return fetch(apiUrl('cities', { province_code: provinceCode }), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            })
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    fillCitiesFromData(data);
                    citySel.disabled = false;
                    return data;
                })
                .catch(function () {
                    citySel.innerHTML = '';
                    var ox = document.createElement('option');
                    ox.value = '';
                    ox.textContent = 'Could not load cities';
                    citySel.appendChild(ox);
                    citySel.disabled = false;
                });
        }

        function loadProvinces() {
            fetch(apiUrl('provinces'), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            })
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    var list = data.provinces || [];
                    var frag = document.createDocumentFragment();
                    var opt0 = document.createElement('option');
                    opt0.value = '';
                    opt0.textContent = 'Select province';
                    frag.appendChild(opt0);
                    list.forEach(function (p) {
                        var o = document.createElement('option');
                        o.value = p.name;
                        o.textContent = p.name;
                        o.setAttribute('data-code', p.code);
                        frag.appendChild(o);
                    });
                    provinceSel.innerHTML = '';
                    provinceSel.appendChild(frag);

                    if (data.source === 'database') {
                        setHint(
                            'Provinces loaded from your database (offline). Cities appear if seeded in ph_cities; otherwise use search or pick NCR/other when online.'
                        );
                    } else if (data.source === 'psgc') {
                        setHint(
                            'Provinces from PSGC (online). Search uses OpenStreetMap (Philippines only). Offline? Use province/city lists from the database after migrations 049–050.'
                        );
                    } else if (data.message) {
                        setHint(data.message);
                    }
                })
                .catch(function () {
                    setHint('Could not load provinces. Run migrations 049–050 or check your connection.');
                })
                .finally(function () {
                    document.dispatchEvent(
                        new CustomEvent('ejeep:passenger-provinces-ready', {
                            detail: {
                                provinceSel: provinceSel,
                                citySel: citySel,
                                loadCities: loadCities,
                            },
                        })
                    );
                });
        }

        provinceSel.addEventListener('change', function () {
            var opt = provinceSel.selectedOptions[0];
            var code = opt ? opt.getAttribute('data-code') : '';
            citySel.innerHTML = '';
            var o = document.createElement('option');
            o.value = '';
            if (!code) {
                o.textContent = 'Select province first';
                citySel.appendChild(o);
                citySel.disabled = true;
                return;
            }
            loadCities(code);
        });

        function applyNominatimResult(item) {
            var a1 = document.getElementById('address_line1');
            if (a1 && item.address_line1) {
                a1.value = item.address_line1;
            }
            var pc = document.getElementById('postal_code');
            if (pc && item.postal_code) {
                pc.value = item.postal_code;
            }
            var po = bestProvinceOption(provinceSel, item.province);
            if (po) {
                var code = po.getAttribute('data-code');
                provinceSel.value = po.value;
                loadCities(code).then(function () {
                    if (item.city) {
                        if (!matchCityOption(citySel, item.city)) {
                            addCityOptionIfMissing(citySel, item.city);
                        }
                    }
                });
            } else if (item.city) {
                addCityOptionIfMissing(citySel, item.city);
            }
        }

        function applyLocalSuggestion(s) {
            var i;
            if (s.province_code) {
                var pc = String(s.province_code);
                for (i = 0; i < provinceSel.options.length; i++) {
                    if (provinceSel.options[i].getAttribute('data-code') === pc) {
                        provinceSel.selectedIndex = i;
                        break;
                    }
                }
            }
            if (provinceSel.selectedIndex <= 0 && s.province) {
                for (i = 0; i < provinceSel.options.length; i++) {
                    if (norm(provinceSel.options[i].value) === norm(s.province)) {
                        provinceSel.selectedIndex = i;
                        break;
                    }
                }
            }
            var opt = provinceSel.selectedOptions[0];
            var code = opt ? opt.getAttribute('data-code') : s.province_code;
            if (!code) {
                return;
            }
            provinceSel.dispatchEvent(new Event('change'));
            loadCities(code).then(function () {
                if (s.city) {
                    if (!matchCityOption(citySel, s.city)) {
                        addCityOptionIfMissing(citySel, s.city);
                    }
                }
            });
        }

        function renderNominatimSuggestions(results) {
            suggList.innerHTML = '';
            results.forEach(function (item) {
                var li = document.createElement('li');
                li.setAttribute('role', 'option');
                var shortLabel = (item.label || '').split(',').slice(0, 3).join(',');
                var main = document.createElement('span');
                main.textContent = shortLabel;
                li.appendChild(main);
                var meta = document.createElement('span');
                meta.className = 'address-suggestions__meta';
                meta.textContent = [item.city, item.province].filter(Boolean).join(' · ');
                li.appendChild(meta);
                li.dataset.kind = 'nominatim';
                li.dataset.payload = JSON.stringify(item);
                suggList.appendChild(li);
            });
            showSugg();
        }

        function renderLocalSuggestions(suggestions) {
            suggList.innerHTML = '';
            if (!suggestions.length) {
                hideSugg();
                return;
            }
            suggestions.forEach(function (s) {
                var li = document.createElement('li');
                li.setAttribute('role', 'option');
                li.textContent = s.label;
                li.dataset.kind = 'local';
                li.dataset.payload = JSON.stringify(s);
                suggList.appendChild(li);
            });
            showSugg();
        }

        function runSearch(q) {
            if (q.length < 3) {
                hideSugg();
                return;
            }
            fetch(apiUrl('geocode', { q: q }), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            })
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    var results = data.results || [];
                    if (results.length) {
                        renderNominatimSuggestions(results);
                        return Promise.resolve(null);
                    }
                    return fetch(apiUrl('local_search', { q: q }), {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });
                })
                .then(function (r) {
                    if (r == null || typeof r.json !== 'function') {
                        return null;
                    }
                    return r.json();
                })
                .then(function (data) {
                    if (data && data.suggestions) {
                        renderLocalSuggestions(data.suggestions);
                    }
                })
                .catch(function () {
                    fetch(apiUrl('local_search', { q: q }), {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    })
                        .then(function (r2) {
                            return r2.json();
                        })
                        .then(function (data2) {
                            renderLocalSuggestions(data2.suggestions || []);
                        })
                        .catch(function () {
                            hideSugg();
                        });
                });
        }

        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            var q = searchInput.value.trim();
            debounceTimer = setTimeout(function () {
                runSearch(q);
            }, 480);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                hideSugg();
            }
        });

        suggList.addEventListener('click', function (e) {
            var li = e.target.closest('li');
            if (!li || !li.dataset.kind) {
                return;
            }
            try {
                if (li.dataset.kind === 'nominatim') {
                    applyNominatimResult(JSON.parse(li.dataset.payload));
                } else {
                    applyLocalSuggestion(JSON.parse(li.dataset.payload));
                }
            } catch (err) {
                /* ignore */
            }
            hideSugg();
            searchInput.value = '';
        });

        document.addEventListener('mousedown', function (e) {
            if (!wrap || wrap.contains(e.target)) {
                return;
            }
            hideSugg();
        });

        loadProvinces();
    });
})();
