/* Revista Speed - confirmaciones y visor de fotos (sin librerías) */
(function () {
    // formularios con data-confirmar
    document.addEventListener('submit', function (ev) {
        var msg = ev.target.getAttribute && ev.target.getAttribute('data-confirmar');
        if (msg && !window.confirm(msg)) {
            ev.preventDefault();
        }
    });

    // visor de fotos (galería de evidencias)
    var enlaces = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox]'));
    if (!enlaces.length) { return; }

    var lb = document.createElement('div');
    lb.className = 'lightbox';
    lb.innerHTML = '<button class="lb-cerrar" aria-label="Cerrar">&times;</button>' +
                   '<button class="lb-prev" aria-label="Anterior">&#8249;</button>' +
                   '<img alt="">' +
                   '<button class="lb-sig" aria-label="Siguiente">&#8250;</button>';
    document.body.appendChild(lb);
    var img = lb.querySelector('img');
    var actual = 0;

    function mostrar(i) {
        actual = (i + enlaces.length) % enlaces.length;
        img.src = enlaces[actual].href;
        lb.classList.add('abierto');
    }
    function cerrar() { lb.classList.remove('abierto'); img.src = ''; }

    enlaces.forEach(function (a, i) {
        a.addEventListener('click', function (ev) { ev.preventDefault(); mostrar(i); });
    });
    lb.querySelector('.lb-cerrar').onclick = cerrar;
    lb.querySelector('.lb-prev').onclick = function () { mostrar(actual - 1); };
    lb.querySelector('.lb-sig').onclick = function () { mostrar(actual + 1); };
    lb.addEventListener('click', function (ev) { if (ev.target === lb) { cerrar(); } });
    document.addEventListener('keydown', function (ev) {
        if (!lb.classList.contains('abierto')) { return; }
        if (ev.key === 'Escape') { cerrar(); }
        if (ev.key === 'ArrowLeft') { mostrar(actual - 1); }
        if (ev.key === 'ArrowRight') { mostrar(actual + 1); }
    });
})();
