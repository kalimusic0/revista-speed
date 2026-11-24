/* Candado del generador ("Añadir publicación").
   La contraseña NO está escrita aquí: solo su huella SHA-256 (con sal).
   Es un candado básico del lado del navegador; publicar de verdad exige entrar a GitHub. */
(function () {
    'use strict';

    var HUELLA = '48a24415cc587be6057dfd7e0df00e50ed541571b7f1c2e4de32df12f0457aa4';
    var SAL = 'revista-speed|';
    var DESTINO = 'generador.html';
    var CLAVE_OK = 'rs_gen_ok';

    /* SHA-256 (funciona también en http, donde crypto.subtle no existe) */
    var K = [0x428a2f98,0x71374491,0xb5c0fbcf,0xe9b5dba5,0x3956c25b,0x59f111f1,0x923f82a4,0xab1c5ed5,0xd807aa98,0x12835b01,0x243185be,0x550c7dc3,0x72be5d74,0x80deb1fe,0x9bdc06a7,0xc19bf174,0xe49b69c1,0xefbe4786,0x0fc19dc6,0x240ca1cc,0x2de92c6f,0x4a7484aa,0x5cb0a9dc,0x76f988da,0x983e5152,0xa831c66d,0xb00327c8,0xbf597fc7,0xc6e00bf3,0xd5a79147,0x06ca6351,0x14292967,0x27b70a85,0x2e1b2138,0x4d2c6dfc,0x53380d13,0x650a7354,0x766a0abb,0x81c2c92e,0x92722c85,0xa2bfe8a1,0xa81a664b,0xc24b8b70,0xc76c51a3,0xd192e819,0xd6990624,0xf40e3585,0x106aa070,0x19a4c116,0x1e376c08,0x2748774c,0x34b0bcb5,0x391c0cb3,0x4ed8aa4a,0x5b9cca4f,0x682e6ff3,0x748f82ee,0x78a5636f,0x84c87814,0x8cc70208,0x90befffa,0xa4506ceb,0xbef9a3f7,0xc67178f2];
    function rr(v, a) { return (v >>> a) | (v << (32 - a)); }
    function sha256(str) {
        var s = unescape(encodeURIComponent(str)), l = s.length, words = [], i, j;
        for (i = 0; i < l; i++) { words[i >> 2] |= s.charCodeAt(i) << (24 - (i % 4) * 8); }
        words[l >> 2] |= 0x80 << (24 - (l % 4) * 8);
        words[(((l + 8) >> 6) + 1) * 16 - 1] = l * 8;
        var H = [0x6a09e667,0xbb67ae85,0x3c6ef372,0xa54ff53a,0x510e527f,0x9b05688c,0x1f83d9ab,0x5be0cd19];
        for (j = 0; j < words.length; j += 16) {
            var w = [], h = H.slice(0);
            for (i = 0; i < 64; i++) {
                if (i < 16) { w[i] = words[j + i] | 0; }
                else {
                    var g0 = w[i - 15], g1 = w[i - 2];
                    w[i] = (w[i - 16] + (rr(g0, 7) ^ rr(g0, 18) ^ (g0 >>> 3)) + w[i - 7] + (rr(g1, 17) ^ rr(g1, 19) ^ (g1 >>> 10))) | 0;
                }
                var a = h[0], e = h[4];
                var t1 = (h[7] + (rr(e, 6) ^ rr(e, 11) ^ rr(e, 25)) + ((e & h[5]) ^ (~e & h[6])) + K[i] + w[i]) | 0;
                var t2 = ((rr(a, 2) ^ rr(a, 13) ^ rr(a, 22)) + ((a & h[1]) ^ (a & h[2]) ^ (h[1] & h[2]))) | 0;
                h = [(t1 + t2) | 0, a, h[1], h[2], (h[3] + t1) | 0, e, h[5], h[6]];
            }
            for (i = 0; i < 8; i++) { H[i] = (H[i] + h[i]) | 0; }
        }
        return H.map(function (x) { return ('00000000' + (x >>> 0).toString(16)).slice(-8); }).join('');
    }

    function autorizado() {
        try { return sessionStorage.getItem(CLAVE_OK) === '1'; } catch (e) { return false; }
    }
    function marcar() { try { sessionStorage.setItem(CLAVE_OK, '1'); } catch (e) {} }

    var fallos = 0, bloqueadoHasta = 0;

    function pedir(alAceptar, alCancelar) {
        if (document.getElementById('acceso')) { return; }
        var m = document.createElement('div');
        m.id = 'acceso';
        m.className = 'acceso';
        m.setAttribute('role', 'dialog');
        m.setAttribute('aria-modal', 'true');
        m.setAttribute('aria-labelledby', 'ac-t');
        m.innerHTML =
            '<form class="acceso-caja" autocomplete="off">' +
            '<h2 id="ac-t">Añadir publicación</h2>' +
            '<p>Escribe la contraseña para entrar al generador de proyectos.</p>' +
            '<input type="password" id="ac-pass" placeholder="Contraseña" autocomplete="off" required>' +
            '<p class="ac-err" id="ac-err" hidden></p>' +
            '<div class="acceso-botones"><button type="button" class="btn-sec" id="ac-cancel">Cancelar</button>' +
            '<button type="submit" class="btn">Entrar</button></div></form>';
        document.body.appendChild(m);
        var inp = document.getElementById('ac-pass'), err = document.getElementById('ac-err');
        inp.focus();

        function cerrar() { m.remove(); }
        document.getElementById('ac-cancel').onclick = function () { cerrar(); if (alCancelar) { alCancelar(); } };
        m.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') { cerrar(); if (alCancelar) { alCancelar(); } } });

        m.querySelector('form').addEventListener('submit', function (ev) {
            ev.preventDefault();
            var ahora = Date.now();
            if (ahora < bloqueadoHasta) {
                err.textContent = 'Demasiados intentos. Espera ' + Math.ceil((bloqueadoHasta - ahora) / 1000) + ' segundos.';
                err.hidden = false;
                return;
            }
            if (sha256(SAL + inp.value.trim()) === HUELLA) {
                marcar();
                cerrar();
                alAceptar();
            } else {
                fallos++;
                if (fallos >= 5) { bloqueadoHasta = Date.now() + 30000; fallos = 0; }
                err.textContent = 'Contraseña incorrecta.';
                err.hidden = false;
                inp.value = '';
                inp.focus();
            }
        });
    }

    // Botón "Añadir publicación" (cualquier enlace con data-acceso)
    document.addEventListener('click', function (ev) {
        var a = ev.target.closest ? ev.target.closest('[data-acceso]') : null;
        if (!a) { return; }
        ev.preventDefault();
        if (autorizado()) { location.href = DESTINO; return; }
        pedir(function () { location.href = DESTINO; });
    });

    // Protección de la propia página del generador
    if (document.body && document.body.hasAttribute('data-protegida')) {
        var abrir = function () { document.documentElement.classList.remove('bloqueado'); };
        if (autorizado()) { abrir(); }
        else { pedir(abrir, function () { location.href = 'index.html'; }); }
    }
})();
