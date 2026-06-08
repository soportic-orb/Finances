/*
 * JS d'aplicació (local, sense CDN).
 *
 * Adjunta el token CSRF a totes les peticions fetch que modifiquen estat,
 * llegint-lo de <meta name="csrf-token">.
 */
(function () {
    'use strict';

    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.getAttribute('content') : null;
    if (!token || !window.fetch) {
        return;
    }

    var original = window.fetch;
    window.fetch = function (input, init) {
        init = init || {};
        var method = (init.method || 'GET').toUpperCase();
        if (['POST', 'PUT', 'PATCH', 'DELETE'].indexOf(method) !== -1) {
            init.headers = new Headers(init.headers || {});
            if (!init.headers.has('X-CSRF-Token')) {
                init.headers.set('X-CSRF-Token', token);
            }
        }
        return original.call(this, input, init);
    };
})();

/*
 * Indicador "escrivint…" al xat d'IA: en enviar la pregunta, mostra la pregunta
 * i tres punts saltarins mentre s'espera la resposta (la pàgina es recarrega amb
 * la resposta final).
 */
(function () {
    'use strict';

    function init() {
        var form = document.querySelector('form[data-chat]');
        if (!form) {
            return;
        }
        form.addEventListener('submit', function () {
            var input = form.querySelector('input[name="question"]');
            var log = document.getElementById('chat-log');
            if (!input || !log || input.value.trim() === '') {
                return; // deixa que la validació HTML actuï
            }
            var empty = document.getElementById('chat-empty');
            if (empty) {
                empty.remove();
            }

            var q = document.createElement('div');
            q.className = 'chat__q';
            q.innerHTML = '<strong>' + (form.dataset.you || '') + ':</strong> ';
            q.appendChild(document.createTextNode(input.value));
            log.appendChild(q);

            var a = document.createElement('div');
            a.className = 'chat__a';
            a.innerHTML = '<strong>' + (form.dataset.assistant || '') + ':</strong> '
                + '<span class="typing-dots" aria-label="…"><span></span><span></span><span></span></span>';
            log.appendChild(a);

            a.scrollIntoView({ block: 'end' });

            var btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
            }
            input.readOnly = true;
            // No es fa preventDefault: el formulari s'envia i la pàgina es recarrega.
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

/*
 * Copilot financer flotant (disseny tipus SysRevAI): botó a totes les pàgines
 * que obre un panell de xat asíncron amb historial, estat expandit, neteja i
 * indicador d'escriptura. Les respostes de l'assistent arriben ja en HTML segur
 * (Markdown renderitzat al servidor).
 */
(function () {
    'use strict';

    function init() {
        var root = document.getElementById('copilot');
        if (!root) { return; }

        var askUrl = root.dataset.ask;
        var historyUrl = root.dataset.history;
        var clearUrl = root.dataset.clear;
        var expandKey = root.dataset.expandKey || 'copilot.expanded';

        var panel = document.getElementById('copilotPanel');
        var toggle = document.getElementById('copilotToggle');
        var closeBtn = document.getElementById('copilotClose');
        var clearBtn = document.getElementById('copilotClear');
        var expand = document.getElementById('copilotExpand');
        var msgs = document.getElementById('copilotMessages');
        var form = document.getElementById('copilotForm');
        var input = document.getElementById('copilotInput');
        var greeting = document.getElementById('copilotGreeting');
        if (!panel || !toggle || !form || !input || !msgs) { return; }

        var hydrated = false;
        var sending = false;

        try { if (localStorage.getItem(expandKey) === '1') { setExpanded(true); } } catch (e) {}

        toggle.addEventListener('click', function () { panel.hidden ? openPanel() : closePanel(); });
        if (closeBtn) { closeBtn.addEventListener('click', function (e) { e.preventDefault(); closePanel(); }); }
        if (expand) {
            expand.addEventListener('click', function (e) {
                e.preventDefault();
                setExpanded(!root.classList.contains('is-expanded'));
            });
        }
        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!confirm(root.dataset.confirm || '?')) { return; }
                fetch(clearUrl, { method: 'POST', headers: { 'X-Requested-With': 'fetch' } }).finally(function () {
                    msgs.querySelectorAll('.copilot__msg, .copilot__typing').forEach(function (n) { n.remove(); });
                    if (greeting) { msgs.insertBefore(greeting, msgs.firstChild); greeting.hidden = false; }
                });
            });
        }
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) { closePanel(); } });
        input.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') { e.preventDefault(); form.requestSubmit(); }
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (sending) { return; }
            var text = (input.value || '').trim();
            if (!text) { return; }

            appendBubble('user', text, false);
            input.value = '';
            sending = true;
            var typing = appendTyping();

            var body = new URLSearchParams();
            body.set('question', text);

            fetch(askUrl, { method: 'POST', headers: { 'X-Requested-With': 'fetch' }, body: body })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    typing.remove();
                    if (d && d.ok && d.html) {
                        appendBubble('assistant', d.html, true);
                    } else {
                        appendBubble('assistant', (d && d.error) || root.dataset.error, false);
                    }
                })
                .catch(function () { typing.remove(); appendBubble('assistant', root.dataset.error || 'Error', false); })
                .finally(function () { sending = false; input.focus(); });
        });

        function openPanel() {
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            hydrate();
            setTimeout(function () { input.focus(); scrollDown(); }, 0);
        }
        function closePanel() {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }
        function setExpanded(on) {
            root.classList.toggle('is-expanded', !!on);
            if (expand) { expand.setAttribute('aria-pressed', on ? 'true' : 'false'); }
            try { localStorage.setItem(expandKey, on ? '1' : '0'); } catch (e) {}
        }

        function hydrate() {
            if (hydrated) { return; }
            hydrated = true;
            fetch(historyUrl, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (!d || !d.ok || !d.messages || !d.messages.length) { return; }
                    if (greeting) { greeting.hidden = true; }
                    d.messages.forEach(function (m) { appendBubble(m.role, m.html, true); });
                    scrollDown();
                })
                .catch(function () {});
        }

        function appendBubble(role, content, isHtml) {
            if (greeting) { greeting.hidden = true; }
            var div = document.createElement('div');
            div.className = 'copilot__msg copilot__msg--' + role + (role === 'assistant' ? ' md' : '');
            if (isHtml) { div.innerHTML = content; } else { div.textContent = content; }
            msgs.appendChild(div);
            scrollDown();
            return div;
        }
        function appendTyping() {
            if (greeting) { greeting.hidden = true; }
            var b = document.createElement('div');
            b.className = 'copilot__msg copilot__msg--assistant copilot__typing';
            b.innerHTML = '<span class="copilot__dots"><span></span><span></span><span></span></span>';
            msgs.appendChild(b);
            scrollDown();
            return b;
        }
        function scrollDown() { msgs.scrollTop = msgs.scrollHeight; }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
