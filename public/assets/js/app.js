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
