/* Live Bus Tracker with Leaflet - updates every 1s without blinking */
(function(){
  let map, tileLayer;
  const markers = new Map(); // key: bus id (driver_assign_id), value: { marker, lastSeen }
  let initialized = false;

  // Bus icon for active trips
  const busIcon = L.icon({
    iconUrl: 'assets/icons/bus-marker.png',
    iconSize: [28, 28],
    iconAnchor: [14, 14],
    popupAnchor: [0, -14],
    className: 'ejeep-bus-icon'
  });

  function initMap() {
    if (initialized) return;
    const el = document.getElementById('busTrackerMap');
    if (!el) return;

    // Default center: Philippines
    map = L.map('busTrackerMap', {
      zoomControl: true,
      attributionControl: false
    }).setView([12.8797, 121.7740], 5);

    tileLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
      maxZoom: 19,
      attribution: 'Tiles &copy; Esri — Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
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
    const { id, lat, lng, route_id, ts, trip_id, status, trip_date } = bus;
    const key = String(id);
    const pos = [lat, lng];

    // Determine if trip is for today (expects YYYY-MM-DD in trip_date)
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const todayStr = `${yyyy}-${mm}-${dd}`;

    const isTodayTrip = (typeof trip_date === 'string' && trip_date.slice(0,10) === todayStr);

    // Consider we have an active trip only if it belongs to today
    const hasTrip = Boolean((trip_id || route_id || (status && status.toLowerCase() === 'on_trip')) && isTodayTrip);

    let entry = markers.get(key);
    if (!entry) {
      const marker = L.marker(pos, {
        title: `Bus ${id} (Route ${route_id ?? 'N/A'})\nLast: ${ts}`,
        icon: hasTrip ? busIcon : undefined
      });
      marker.addTo(map);
      marker.bindPopup(`<strong>Bus:</strong> ${id}<br><strong>Route:</strong> ${route_id ?? 'N/A'}<br><strong>Trip Date:</strong> ${trip_date ?? 'N/A'}<br><strong>Last:</strong> ${ts}`);
      entry = { marker, lastSeen: Date.now(), hasTrip };
      markers.set(key, entry);
    } else {
      // Smooth update without recreating
      entry.marker.setLatLng(pos);
      entry.marker.setPopupContent(`<strong>Bus:</strong> ${id}<br><strong>Route:</strong> ${route_id ?? 'N/A'}<br><strong>Trip Date:</strong> ${trip_date ?? 'N/A'}<br><strong>Last:</strong> ${ts}`);
      // Update icon only if state changed to avoid churn
      if ((entry.hasTrip || false) !== hasTrip) {
        entry.marker.setIcon(hasTrip ? busIcon : new L.Icon.Default());
        entry.hasTrip = hasTrip;
      }
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
