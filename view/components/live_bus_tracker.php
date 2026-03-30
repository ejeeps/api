<?php
/**
 * Passenger AI assistant (Groq) — replaces live bus map.
 * Requires: Font Awesome, dashboard.css, assets/script/passenger/ai-assistant.js
 * Parent page must set $basePath (empty string when served from index.php router).
 */
if (!isset($basePath)) {
    $basePath = '';
}
?>
<!-- E-JEEP Passenger Assistant -->
<div id="assistantModal" class="tracker-modal assistant-chat-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="assistantModalTitle">
    <div class="modal-content assistant-chat-shell">
        <header class="assistant-chat-header">
            <div class="assistant-chat-header__brand">
                <div class="assistant-chat-header__avatar" aria-hidden="true">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="assistant-chat-header__text">
                    <h2 class="assistant-chat-header__title" id="assistantModalTitle">E-JEEP Assistant</h2>
                    <p class="assistant-chat-header__subtitle">Trips, routes &amp; balance</p>
                </div>
            </div>
            <button type="button" class="assistant-chat-header__close" id="assistantModalClose" aria-label="Close chat">
                <i class="fas fa-times"></i>
            </button>
        </header>

        <div class="assistant-chat-main">
            <div class="assistant-messages" id="assistantMessages" aria-live="polite">
                <div class="assistant-empty-state" id="assistantEmptyState">
                    <div class="assistant-empty-state__icon">
                        <i class="fas fa-route"></i>
                    </div>
                    <p class="assistant-empty-state__title">How can I help?</p>
                    <p class="assistant-empty-state__hint">Ask about your routes, recent trips, or card balance. Answers use your E-JEEP data only.</p>
                </div>
            </div>

            <div class="assistant-composer">
                <label class="assistant-composer__label visually-hidden" for="assistantInput">Message</label>
                <div class="assistant-composer__inner">
                    <textarea id="assistantInput" class="assistant-composer__input" rows="1" placeholder="Message…" maxlength="4000" autocomplete="off"></textarea>
                    <button type="button" class="assistant-composer__send" id="assistantSendBtn" aria-label="Send message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<button type="button" id="floatingAssistantBtn" class="floating-track-btn floating-assistant-btn" aria-label="Open E-JEEP Assistant">
    <i class="fas fa-comments"></i>
</button>

<script>
window.EJEEP_API_BASE = <?php echo json_encode($basePath, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script src="<?php echo htmlspecialchars($basePath); ?>assets/script/passenger/ai-assistant.js" defer></script>
