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
 * Xat financer flotant: botó a totes les pàgines que obre una finestra de xat
 * asíncrona (fetch). Mostra punts saltarins mentre la IA respon.
 */
(function () {
    'use strict';

    function init() {
        var fab = document.getElementById('ai-fab');
        var widget = document.getElementById('ai-widget');
        var form = document.getElementById('ai-widget-form');
        var log = document.getElementById('ai-widget-log');
        if (!fab || !widget || !form || !log) {
            return;
        }
        var input = form.querySelector('input[name="question"]');
        var btn = form.querySelector('button[type="submit"]');

        function open() {
            widget.hidden = false;
            fab.setAttribute('aria-expanded', 'true');
            setTimeout(function () { input.focus(); }, 50);
        }
        function close() {
            widget.hidden = true;
            fab.setAttribute('aria-expanded', 'false');
        }
        fab.addEventListener('click', function () { widget.hidden ? open() : close(); });
        var closeBtn = document.getElementById('ai-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', close);
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !widget.hidden) { close(); }
        });

        function bubble(cls, label) {
            var d = document.createElement('div');
            d.className = cls;
            d.innerHTML = '<strong>' + label + ':</strong> ';
            log.appendChild(d);
            return d;
        }
        function scrollDown() { log.scrollTop = log.scrollHeight; }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var q = input.value.trim();
            if (q === '') { return; }
            var empty = document.getElementById('ai-widget-empty');
            if (empty) { empty.remove(); }

            var qb = bubble('chat__q', widget.dataset.you || '');
            qb.appendChild(document.createTextNode(q));

            var ab = bubble('chat__a', widget.dataset.assistant || '');
            ab.innerHTML += '<span class="typing-dots"><span></span><span></span><span></span></span>';

            input.value = '';
            input.disabled = true;
            if (btn) { btn.disabled = true; }
            scrollDown();

            var body = new URLSearchParams();
            body.set('question', q);

            fetch(widget.dataset.ask, {
                method: 'POST',
                headers: { 'X-Requested-With': 'fetch' },
                body: body
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data && data.ok) {
                    ab.className = 'chat__a md';
                    ab.innerHTML = '<strong>' + (widget.dataset.assistant || '') + ':</strong> ' + data.html;
                } else {
                    ab.innerHTML = '<strong>' + (widget.dataset.assistant || '') + ':</strong> ';
                    ab.appendChild(document.createTextNode((data && data.error) || widget.dataset.error || 'Error'));
                }
            }).catch(function () {
                ab.innerHTML = '<strong>' + (widget.dataset.assistant || '') + ':</strong> ';
                ab.appendChild(document.createTextNode(widget.dataset.error || 'Error'));
            }).finally(function () {
                input.disabled = false;
                if (btn) { btn.disabled = false; }
                input.focus();
                scrollDown();
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
