/**
 * E-JEEP passenger AI assistant — chat UI + PassengerAiAssistantController.
 */
(function () {
    'use strict';

    var base = typeof window.EJEEP_API_BASE === 'string' ? window.EJEEP_API_BASE : '';
    var history = [];

    function endpoint() {
        return base + 'controller/passenger/PassengerAiAssistantController.php';
    }

    function el(id) {
        return document.getElementById(id);
    }

    function removeEmptyState() {
        var empty = el('assistantEmptyState');
        if (empty && empty.parentNode) {
            empty.parentNode.removeChild(empty);
        }
    }

    function appendMsg(role, text, isError) {
        var box = el('assistantMessages');
        if (!box) return;
        removeEmptyState();

        var row = document.createElement('div');
        row.className = 'assistant-msg-row assistant-msg-row--' + (role === 'user' ? 'user' : 'bot');

        var bubble = document.createElement('div');
        bubble.className = 'assistant-msg-bubble assistant-msg-bubble--' + (role === 'user' ? 'user' : 'bot');
        if (isError) {
            bubble.classList.add('assistant-msg-bubble--error');
        }
        bubble.textContent = text;

        if (role === 'user') {
            row.appendChild(bubble);
        } else {
            var av = document.createElement('div');
            av.className = 'assistant-msg-avatar';
            av.setAttribute('aria-hidden', 'true');
            av.innerHTML = '<i class="fas fa-robot"></i>';
            row.appendChild(av);
            row.appendChild(bubble);
        }

        box.appendChild(row);
        box.scrollTop = box.scrollHeight;
    }

    /**
     * Assistant reply with typewriter effect (errors show instantly).
     */
    function appendAssistantMessageTyped(text, isError, onComplete) {
        var box = el('assistantMessages');
        if (!box) {
            if (onComplete) onComplete();
            return;
        }
        removeEmptyState();

        var row = document.createElement('div');
        row.className = 'assistant-msg-row assistant-msg-row--bot';

        var av = document.createElement('div');
        av.className = 'assistant-msg-avatar';
        av.setAttribute('aria-hidden', 'true');
        av.innerHTML = '<i class="fas fa-robot"></i>';

        var bubble = document.createElement('div');
        bubble.className = 'assistant-msg-bubble assistant-msg-bubble--bot';
        if (isError) {
            bubble.classList.add('assistant-msg-bubble--error');
        } else {
            bubble.classList.add('assistant-msg-bubble--streaming');
        }

        row.appendChild(av);
        row.appendChild(bubble);
        box.appendChild(row);

        if (isError || !text) {
            bubble.textContent = text || '';
            box.scrollTop = box.scrollHeight;
            if (onComplete) onComplete();
            return;
        }

        var cursor = document.createElement('span');
        cursor.className = 'assistant-cursor';
        cursor.setAttribute('aria-hidden', 'true');

        var full = String(text);
        var i = 0;
        var len = full.length;
        var chunk = len > 900 ? 4 : len > 400 ? 3 : len > 120 ? 2 : 1;
        var delayMs = len > 900 ? 14 : len > 400 ? 16 : 18;

        function tick() {
            if (!bubble.parentNode) {
                if (onComplete) onComplete();
                return;
            }
            i = Math.min(len, i + chunk);
            bubble.textContent = full.slice(0, i);
            if (i < len) {
                bubble.appendChild(cursor);
            } else {
                bubble.classList.remove('assistant-msg-bubble--streaming');
                if (cursor.parentNode) {
                    cursor.parentNode.removeChild(cursor);
                }
                if (onComplete) onComplete();
            }
            box.scrollTop = box.scrollHeight;
            if (i < len) {
                setTimeout(tick, delayMs);
            }
        }

        tick();
    }

    function showTyping() {
        var box = el('assistantMessages');
        if (!box) return;
        removeEmptyState();
        hideTyping();

        var row = document.createElement('div');
        row.className = 'assistant-msg-row assistant-msg-row--bot assistant-msg-row--typing';
        row.id = 'assistantTypingRow';

        var av = document.createElement('div');
        av.className = 'assistant-msg-avatar';
        av.setAttribute('aria-hidden', 'true');
        av.innerHTML = '<i class="fas fa-robot"></i>';

        var bubble = document.createElement('div');
        bubble.className = 'assistant-msg-bubble assistant-msg-bubble--bot';
        bubble.innerHTML = '<span class="assistant-typing-dots" aria-label="Thinking"><span></span><span></span><span></span></span>';

        row.appendChild(av);
        row.appendChild(bubble);
        box.appendChild(row);
        box.scrollTop = box.scrollHeight;
    }

    function hideTyping() {
        var t = el('assistantTypingRow');
        if (t && t.parentNode) {
            t.parentNode.removeChild(t);
        }
    }

    function setLoading(on) {
        var btn = el('assistantSendBtn');
        var input = el('assistantInput');
        if (btn) {
            btn.disabled = on;
            btn.setAttribute('aria-busy', on ? 'true' : 'false');
        }
        if (input) input.disabled = on;
        if (on) {
            showTyping();
        } else {
            hideTyping();
        }
    }

    function autoResizeTextarea() {
        var input = el('assistantInput');
        if (!input || input.tagName !== 'TEXTAREA') return;
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
    }

    function openModal() {
        var modal = el('assistantModal');
        if (!modal) return;
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        switchView('selector');
        var input = el('assistantInput');
        if (input) {
            setTimeout(function () {
                input.focus();
                autoResizeTextarea();
            }, 100);
        }
    }

    function closeModal() {
        var modal = el('assistantModal');
        if (!modal) return;
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function switchView(mode) {
        var selector = el('assistantModeSelector');
        var aiView = el('assistantAiView');
        var csView = el('assistantCsView');
        if (!selector || !aiView || !csView) return;

        selector.hidden = mode !== 'selector';
        aiView.hidden = mode !== 'ai';
        csView.hidden = mode !== 'cs';

        if (mode === 'ai') {
            var input = el('assistantInput');
            if (input) {
                setTimeout(function () { input.focus(); }, 50);
            }
        }
        if (mode === 'cs') {
            var frame = el('assistantCsFrame');
            if (frame && !frame.getAttribute('src')) {
                frame.setAttribute('src', base + 'index.php?page=customer_service&embed=1');
            }
        }
    }

    async function sendMessage() {
        var input = el('assistantInput');
        if (!input) return;
        var text = (input.value || '').trim();
        if (!text) return;

        input.value = '';
        autoResizeTextarea();
        appendMsg('user', text);
        setLoading(true);

        try {
            var res = await fetch(endpoint(), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: text,
                    history: history
                }),
                credentials: 'same-origin',
                cache: 'no-store'
            });
            var data = await res.json().catch(function () { return {}; });
            hideTyping();
            if (!res.ok) {
                appendMsg('assistant', data.error || 'Something went wrong. Try again.', true);
                return;
            }
            var reply = data.reply || '';
            appendAssistantMessageTyped(reply, false);
            history.push({ role: 'user', content: text });
            history.push({ role: 'assistant', content: reply });
            if (history.length > 20) {
                history = history.slice(-20);
            }
        } catch (e) {
            hideTyping();
            appendMsg('assistant', 'Network error. Check your connection and try again.', true);
        } finally {
            setLoading(false);
        }
    }

    function init() {
        var btn = el('floatingAssistantBtn');
        var modal = el('assistantModal');
        var closeBtn = el('assistantModalClose');
        var sendBtn = el('assistantSendBtn');
        var input = el('assistantInput');
        var modeAiBtn = el('assistantModeAiBtn');
        var modeCsBtn = el('assistantModeCsBtn');
        var backAi = el('assistantBackFromAi');
        var backCs = el('assistantBackFromCs');

        if (btn && modal) {
            btn.addEventListener('click', openModal);
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function (ev) {
                if (ev.target === modal) closeModal();
            });
            document.addEventListener('keydown', function (ev) {
                if (ev.key === 'Escape' && modal.classList.contains('open')) closeModal();
            });
        }

        if (sendBtn) sendBtn.addEventListener('click', sendMessage);
        if (modeAiBtn) modeAiBtn.addEventListener('click', function () { switchView('ai'); });
        if (modeCsBtn) modeCsBtn.addEventListener('click', function () { switchView('cs'); });
        if (backAi) backAi.addEventListener('click', function () { switchView('selector'); });
        if (backCs) backCs.addEventListener('click', function () { switchView('selector'); });
        if (input) {
            input.addEventListener('input', autoResizeTextarea);
            input.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' && !ev.shiftKey) {
                    ev.preventDefault();
                    sendMessage();
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
