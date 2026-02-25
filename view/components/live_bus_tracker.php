<?php
/**
 * Live Bus Tracker Component
 * Include this file in any page that needs the live bus tracking feature
 * Requires: Font Awesome CSS, live-tracker.js
 */
?>
<!-- Live Bus Tracker Modal -->
<div id="trackerModal" class="tracker-modal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">Live Bus Tracker</div>
            <button type="button" class="modal-close" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="busTrackerMap"></div>
        </div>
    </div>
</div>

<!-- Floating Live Tracking Button -->
<button id="floatingTrackBtn" class="floating-track-btn" aria-label="Live Bus Tracker">
    <i class="fas fa-location-arrow"></i>
</button>
