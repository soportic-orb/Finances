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
