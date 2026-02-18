/* Live Bus Tracker with Leaflet - updates every 1s without blinking */
(function(){
  let map, tileLayer;
  const markers = new Map(); // key: bus id (driver_assign_id), value: { marker, lastSeen }
  let initialized = false;

  function initMap() {
    if (initialized) return;
    const el = document.getElementById('busTrackerMap');
    if (!el) return;

    // Default center: Philippines
    map = L.map('busTrackerMap', {
      zoomControl: true,
      attributionControl: true
    }).setView([12.8797, 121.7740], 5);

    tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    });
    tileLayer.addTo(map);

    initialized = true;
    setTimeout(() => map.invalidateSize(), 0);
  }

  async function fetchPositions() {
    try {
      const res = await fetch('controller/passenger/get_live_positions.php', { cache: 'no-store' });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return await res.json();
    } catch (e) {
      // Silently ignore transient errors to avoid UI flicker
      return [];
    }
  }

  function upsertMarker(bus) {
    const { id, lat, lng, route_id, ts } = bus;
    const key = String(id);
    const pos = [lat, lng];

    let entry = markers.get(key);
    if (!entry) {
      const marker = L.marker(pos, {
        title: `Bus ${id} (Route ${route_id})\nLast: ${ts}`
      });
      marker.addTo(map);
      marker.bindPopup(`<strong>Bus:</strong> ${id}<br><strong>Route:</strong> ${route_id}<br><strong>Last:</strong> ${ts}`);
      entry = { marker, lastSeen: Date.now() };
      markers.set(key, entry);
    } else {
      // Smooth update without recreating
      entry.marker.setLatLng(pos);
      entry.marker.setPopupContent(`<strong>Bus:</strong> ${id}<br><strong>Route:</strong> ${route_id}<br><strong>Last:</strong> ${ts}`);
      entry.lastSeen = Date.now();
    }
  }

  function cleanupStale() {
    const now = Date.now();
    const ttl = 15000; // remove markers not seen for > 15s
    for (const [key, entry] of markers.entries()) {
      if (now - entry.lastSeen > ttl) {
        map.removeLayer(entry.marker);
        markers.delete(key);
      }
    }
  }

  async function tick() {
    if (!initialized) return;
    const data = await fetchPositions();
    if (Array.isArray(data)) {
      for (const bus of data) upsertMarker(bus);
      cleanupStale();
    }
  }

  function start() {
    initMap();
    // Poll every 1s
    setInterval(tick, 1000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
