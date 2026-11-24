/* Revista Speed - lógica del sitio estático (sin librerías).
   Lee data/proyectos.json y, si está configurado, guarda/lee calificaciones en Supabase. */
(function () {
    'use strict';

    var CFG = window.REVISTA_CONFIG || {};
    var CON_ESTRELLAS = !!(CFG.supabaseUrl && CFG.supabaseKey);

    /* Las calificaciones se habilitan el día del evento (CFG.fechaEvento) o antes si CFG.calificacionesAbiertas === true. */
    function hoyYMD() {
        var d = new Date();
        return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
    }
    var VOTO_ABIERTO = CFG.calificacionesAbiertas === true || !!(CFG.fechaEvento && hoyYMD() >= CFG.fechaEvento);
    var MESES = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto',
                 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    /* ---------- utilidades ---------- */
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function nl2br(s) { return esc(s).replace(/\n/g, '<br>'); }
    function $(sel, raiz) { return (raiz || document).querySelector(sel); }

    // solo rutas internas (img/...) o https: nada de javascript: ni rutas raras
    function rutaSegura(u) {
        u = String(u || '');
        if (u.indexOf('..') !== -1) { return ''; }
        return /^(img\/|assets\/|https:\/\/)/.test(u) ? u : '';
    }
    function fechaBonita(ymd) {
        var p = String(ymd || '').split('-');
        if (p.length < 3) { return esc(ymd); }
        return parseInt(p[2], 10) + ' de ' + MESES[parseInt(p[1], 10)] + ' de ' + p[0];
    }
    function estrellasHtml(prom) {
        var h = '<span class="estrellas" aria-label="' + prom.toFixed(1) + ' de 5">';
        for (var i = 1; i <= 5; i++) {
            var c = prom >= i - 0.25 ? 'llena' : (prom >= i - 0.75 ? 'media' : '');
            h += '<span class="e ' + c + '">&#9733;</span>';
        }
        return h + '</span>';
    }
    function videoEmbed(url) {
        var m = String(url || '').match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([A-Za-z0-9_-]{11})/);
        return m ? 'https://www.youtube.com/embed/' + m[1] : '';
    }
    function getJSON(url, opts) {
        return fetch(url, opts).then(function (r) {
            if (!r.ok) { throw new Error('HTTP ' + r.status); }
            return r.json();
        });
    }

    /* ---------- datos ---------- */
    function cargarProyectos() {
        return getJSON('data/proyectos.json?v=' + Date.now());
    }
    function sbHeaders() {
        return { apikey: CFG.supabaseKey, Authorization: 'Bearer ' + CFG.supabaseKey, 'Content-Type': 'application/json' };
    }
    function cargarValoraciones() {
        if (!CON_ESTRELLAS) { return Promise.resolve([]); }
        var url = CFG.supabaseUrl + '/rest/v1/valoraciones?select=proyecto,estrellas,nombre,comentario,created_at&order=created_at.desc&limit=5000';
        return getJSON(url, { headers: sbHeaders() }).catch(function () { return []; });
    }
    function agrupar(vals) {
        var g = {};
        vals.forEach(function (v) {
            var x = g[v.proyecto] || (g[v.proyecto] = { suma: 0, n: 0 });
            x.suma += v.estrellas;
            x.n++;
        });
        return g;
    }
    function huella() {
        var h = '';
        try { h = localStorage.getItem('rs_huella') || ''; } catch (e) {}
        if (!h) {
            h = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() :
                ('h' + Date.now().toString(36) + Math.random().toString(36).slice(2, 12));
            try { localStorage.setItem('rs_huella', h); } catch (e) {}
        }
        return h;
    }
    function yaVoto(id) {
        try { return !!localStorage.getItem('rs_voto_' + id); } catch (e) { return false; }
    }

    /* ================= PORTADA ================= */
    function iniciarPortada() {
        var cont = $('#rejilla');
        if (!cont) { return; }

        if (CFG.fechaEvento) {
            var dias = Math.floor((new Date(CFG.fechaEvento + 'T00:00:00') - new Date(new Date().toDateString())) / 86400000);
            var el = $('#cuenta');
            if (el && dias >= 0) {
                el.textContent = dias === 0 ? '¡Es hoy!' : 'Faltan ' + dias + ' día' + (dias === 1 ? '' : 's');
                el.hidden = false;
            }
        }

        Promise.all([cargarProyectos(), cargarValoraciones()]).then(function (r) {
            var proyectos = r[0], stats = agrupar(r[1]);

            // cursos para el filtro
            var cursos = [];
            proyectos.forEach(function (p) { if (cursos.indexOf(p.curso) === -1) { cursos.push(p.curso); } });
            cursos.sort();
            var sel = $('#f-curso');
            cursos.forEach(function (c) { sel.insertAdjacentHTML('beforeend', '<option>' + esc(c) + '</option>'); });

            // destacados
            var top = proyectos.filter(function (p) { return stats[p.id]; }).map(function (p) {
                return { p: p, prom: stats[p.id].suma / stats[p.id].n, n: stats[p.id].n };
            }).sort(function (a, b) { return b.prom - a.prom || b.n - a.n; }).slice(0, 3);
            var dest = $('#destacados');
            if (top.length) {
                dest.hidden = false;
                $('#dest-fila').innerHTML = top.map(function (t, i) {
                    return '<a class="dest" href="proyecto.html?id=' + encodeURIComponent(t.p.id) + '">' +
                        '<span class="dest-n">#' + (i + 1) + '</span><span class="dest-t">' + esc(t.p.titulo) + '</span>' +
                        '<span class="dest-c">' + esc(t.p.curso) + '</span>' +
                        estrellasHtml(t.prom) + ' <small>' + t.prom.toFixed(1) + ' (' + t.n + ')</small></a>';
                }).join('');
            }

            function pintar() {
                var q = $('#f-q').value.trim().toLowerCase();
                var curso = $('#f-curso').value;
                var asc = $('#f-orden').value === 'antiguas';
                var lista = proyectos.filter(function (p) {
                    if (curso && p.curso !== curso) { return false; }
                    if (!q) { return true; }
                    var txt = (p.titulo + ' ' + p.resena + ' ' + (p.expositores || []).join(' ')).toLowerCase();
                    return txt.indexOf(q) !== -1;
                }).sort(function (a, b) {
                    var d = String(a.fecha).localeCompare(String(b.fecha));
                    return asc ? d : -d;
                });

                if (!lista.length) {
                    cont.innerHTML = '<p class="vacio" style="grid-column:1/-1">No hay proyectos' +
                        (q || curso ? ' con esos filtros' : ' publicados todavía') + '.</p>';
                    return;
                }
                cont.innerHTML = lista.map(function (p) {
                    var s = stats[p.id];
                    var img = rutaSegura(p.portada);
                    var nota = s ? estrellasHtml(s.suma / s.n) + ' <small>' + (s.suma / s.n).toFixed(1) + ' (' + s.n + ')</small>'
                                 : '<small class="tenue">' + (VOTO_ABIERTO && CON_ESTRELLAS ? 'Sin calificaciones aún' : 'Calificaciones el día del evento') + '</small>';
                    var href = 'proyecto.html?id=' + encodeURIComponent(p.id);
                    return '<article class="tarjeta">' +
                        '<a class="tarjeta-img" href="' + href + '">' +
                        (img ? '<img src="' + esc(img) + '" alt="' + esc(p.titulo) + '" loading="lazy">' : '<span class="sin-img">Sin imagen</span>') + '</a>' +
                        '<div class="tarjeta-cuerpo">' +
                        '<p class="meta"><span class="etq">' + esc(p.curso) + '</span> <time>' + fechaBonita(p.fecha) + '</time></p>' +
                        '<h3><a href="' + href + '">' + esc(p.titulo) + '</a></h3>' +
                        '<p class="resena">' + esc(p.resena) + '</p>' +
                        '<div class="tarjeta-pie"><span>' + nota + '</span><a class="ver-mas" href="' + href + '">Ver más &rarr;</a></div>' +
                        '</div></article>';
                }).join('');
            }

            ['f-q', 'f-curso', 'f-orden'].forEach(function (id) {
                $('#' + id).addEventListener(id === 'f-q' ? 'input' : 'change', pintar);
            });
            pintar();
        }).catch(function () {
            cont.innerHTML = '<p class="vacio" style="grid-column:1/-1">No se pudieron cargar los proyectos. Revisa data/proyectos.json.</p>';
        });
    }

    /* ================= PROYECTO ================= */
    function iniciarProyecto() {
        var cont = $('#articulo');
        if (!cont) { return; }
        var id = new URLSearchParams(location.search).get('id') || '';

        Promise.all([cargarProyectos(), cargarValoraciones()]).then(function (r) {
            var p = null;
            r[0].forEach(function (x) { if (x.id === id) { p = x; } });
            if (!p) {
                cont.innerHTML = '<p class="vacio">Este proyecto no existe.</p><p><a class="btn" href="index.html">Volver</a></p>';
                return;
            }
            document.title = p.titulo + ' · ' + (CFG.nombre || 'Revista Speed');
            var vals = r[1].filter(function (v) { return v.proyecto === p.id; });
            var suma = 0;
            vals.forEach(function (v) { suma += v.estrellas; });
            var prom = vals.length ? suma / vals.length : 0;

            var img = rutaSegura(p.portada);
            var embed = videoEmbed(p.video);
            var fotos = (p.fotos || []).map(rutaSegura).filter(Boolean);

            var html = '';
            html += '<header class="art-cab"><p class="meta"><span class="etq">' + esc(p.curso) + '</span> <time>' + fechaBonita(p.fecha) + '</time>' +
                    (p.stand ? ' · Stand: <strong>' + esc(p.stand) + '</strong>' : '') + '</p>' +
                    '<h1>' + esc(p.titulo) + '</h1><p class="lead">' + esc(p.resena) + '</p>' +
                    '<p class="art-nota">' + (vals.length ? estrellasHtml(prom) + ' <strong>' + prom.toFixed(1) + '</strong> <small>(' + vals.length + (vals.length === 1 ? ' calificación' : ' calificaciones') + ')</small>'
                        : '<small class="tenue">' + (VOTO_ABIERTO && CON_ESTRELLAS ? 'Aún sin calificaciones' : 'Calificaciones el día del evento') + '</small>') + '</p></header>';
            if (img) { html += '<figure class="art-portada"><img src="' + esc(img) + '" alt="' + esc(p.titulo) + '"></figure>'; }

            html += '<div class="art-cols"><div class="art-texto"><h2>Sobre el proyecto</h2><p>' + nl2br(p.descripcion) + '</p>' +
                    '<h2>¿Qué tiene de innovador?</h2><p>' + nl2br(p.innovacion) + '</p>';
            if (embed) {
                html += '<h2>Video</h2><div class="video"><iframe src="' + esc(embed) + '" allowfullscreen loading="lazy" title="Video del proyecto"></iframe></div>';
            } else if (rutaSegura(p.video)) {
                html += '<p><a class="btn-sec" href="' + esc(p.video) + '" target="_blank" rel="noopener">Ver video del proyecto</a></p>';
            }
            html += '</div><aside class="ficha"><h3>Expositores</h3><p>' + nl2br((p.expositores || []).join('\n')) + '</p>' +
                    '<h3>Curso</h3><p>' + esc(p.curso) + '</p>' + (p.stand ? '<h3>Ubicación</h3><p>' + esc(p.stand) + '</p>' : '') + '</aside></div>';

            if (fotos.length) {
                html += '<section><h2>Evidencias fotográficas</h2><div class="galeria">' + fotos.map(function (f) {
                    return '<a href="' + esc(f) + '" class="gal-item" data-lightbox><img src="' + esc(f) + '" alt="Evidencia del proyecto ' + esc(p.titulo) + '" loading="lazy"></a>';
                }).join('') + '</div></section>';
            }

            html += '<section id="resenas" class="resenas"><h2>Califica este proyecto</h2><div id="zona-voto"></div>';
            if (vals.length) {
                html += '<h3>Reseñas de los visitantes</h3><ul class="lista-resenas">' + vals.map(function (v) {
                    return '<li><div>' + estrellasHtml(v.estrellas) + ' <strong>' + esc(v.nombre || 'Visitante') + '</strong> <small class="tenue">' +
                           esc(String(v.created_at || '').slice(0, 10)) + '</small></div>' +
                           (v.comentario ? '<p>' + nl2br(v.comentario) + '</p>' : '') + '</li>';
                }).join('') + '</ul>';
            }
            html += '</section>';
            cont.innerHTML = html;

            iniciarVoto(p, $('#zona-voto'));
            iniciarVisor();
        }).catch(function () {
            cont.innerHTML = '<p class="vacio">No se pudo cargar el proyecto.</p><p><a class="btn" href="index.html">Volver</a></p>';
        });
    }

    function iniciarVoto(p, zona) {
        if (!CON_ESTRELLAS || !VOTO_ABIERTO) {
            zona.innerHTML = '<p class="aviso aviso-info">&#9733; Las calificaciones y reseñas estarán disponibles <strong>el día del evento</strong>' +
                (CFG.fechaEvento ? ' (' + fechaBonita(CFG.fechaEvento) + ')' : '') + '. ¡Vuelve ese día para calificar este proyecto!</p>';
            return;
        }
        if (yaVoto(p.id)) {
            zona.innerHTML = '<p class="aviso aviso-ok">Ya enviaste tu calificación. ¡Gracias por participar!</p>';
            return;
        }
        var radios = '';
        for (var i = 5; i >= 1; i--) {
            radios += '<input type="radio" id="st' + i + '" name="estrellas" value="' + i + '" required>' +
                      '<label for="st' + i + '" title="' + i + ' estrella' + (i > 1 ? 's' : '') + '">&#9733;</label>';
        }
        zona.innerHTML = '<form class="form form-valorar" id="form-voto"><div class="estrellas-input" role="radiogroup" aria-label="Calificación">' + radios + '</div>' +
            '<label>Tu nombre (opcional) <input name="nombre" maxlength="60" autocomplete="off"></label>' +
            '<label>Tu reseña corta (opcional, máx. 300 caracteres)<textarea name="comentario" rows="3" maxlength="300" placeholder="¿Qué te pareció el proyecto?"></textarea></label>' +
            '<p class="aviso aviso-error" id="voto-error" hidden></p>' +
            '<button class="btn" type="submit">Enviar calificación</button></form>';

        $('#form-voto').addEventListener('submit', function (ev) {
            ev.preventDefault();
            var f = ev.target, err = $('#voto-error'), btn = $('button', f);
            var est = parseInt((f.querySelector('input[name=estrellas]:checked') || {}).value, 10);
            err.hidden = true;
            if (!(est >= 1 && est <= 5)) { err.textContent = 'Elige de 1 a 5 estrellas.'; err.hidden = false; return; }
            btn.disabled = true;
            fetch(CFG.supabaseUrl + '/rest/v1/valoraciones', {
                method: 'POST',
                headers: Object.assign(sbHeaders(), { Prefer: 'return=minimal' }),
                body: JSON.stringify({
                    proyecto: p.id, estrellas: est,
                    nombre: f.nombre.value.trim().slice(0, 60),
                    comentario: f.comentario.value.trim().slice(0, 300),
                    huella: huella()
                })
            }).then(function (r) {
                if (r.ok || r.status === 409) {
                    try { localStorage.setItem('rs_voto_' + p.id, '1'); } catch (e) {}
                    location.hash = 'resenas';
                    location.reload();
                } else { throw new Error('HTTP ' + r.status); }
            }).catch(function () {
                btn.disabled = false;
                err.textContent = 'No se pudo enviar la calificación. Inténtalo de nuevo en un momento.';
                err.hidden = false;
            });
        });
    }

    /* visor de fotos (galería de evidencias) */
    function iniciarVisor() {
        var enlaces = Array.prototype.slice.call(document.querySelectorAll('[data-lightbox]'));
        if (!enlaces.length) { return; }
        var lb = document.createElement('div');
        lb.className = 'lightbox';
        lb.innerHTML = '<button class="lb-cerrar" aria-label="Cerrar">&times;</button>' +
                       '<button class="lb-prev" aria-label="Anterior">&#8249;</button><img alt="">' +
                       '<button class="lb-sig" aria-label="Siguiente">&#8250;</button>';
        document.body.appendChild(lb);
        var img = $('img', lb), actual = 0;
        function mostrar(i) { actual = (i + enlaces.length) % enlaces.length; img.src = enlaces[actual].getAttribute('href'); lb.classList.add('abierto'); }
        function cerrar() { lb.classList.remove('abierto'); img.removeAttribute('src'); }
        enlaces.forEach(function (a, i) { a.addEventListener('click', function (ev) { ev.preventDefault(); mostrar(i); }); });
        $('.lb-cerrar', lb).onclick = cerrar;
        $('.lb-prev', lb).onclick = function () { mostrar(actual - 1); };
        $('.lb-sig', lb).onclick = function () { mostrar(actual + 1); };
        lb.addEventListener('click', function (ev) { if (ev.target === lb) { cerrar(); } });
        document.addEventListener('keydown', function (ev) {
            if (!lb.classList.contains('abierto')) { return; }
            if (ev.key === 'Escape') { cerrar(); }
            if (ev.key === 'ArrowLeft') { mostrar(actual - 1); }
            if (ev.key === 'ArrowRight') { mostrar(actual + 1); }
        });
    }


    /* ================= PANTALLA "PRÓXIMAMENTE" (solo portada) ================= */
    function iniciarSplash() {
        var sp = $('#splash');
        if (!sp) { return; }
        var llegoElDia = !!(CFG.fechaEvento && hoyYMD() >= CFG.fechaEvento);
        if (CFG.pantallaProximamente !== true || llegoElDia) { sp.remove(); return; }

        var cerrado = false;
        function cerrar() {
            if (cerrado) { return; }
            cerrado = true;
            try { sessionStorage.setItem('rs_splash', '1'); } catch (e) {}
            sp.classList.add('saliendo');
            document.body.classList.remove('sin-scroll');
            setTimeout(function () { sp.remove(); }, 450);
        }
        document.body.classList.add('sin-scroll');
        $('#splash-btn').addEventListener('click', cerrar);
        $('#splash-x').addEventListener('click', cerrar);
        document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') { cerrar(); } });
        $('#splash-btn').focus();

        // cuenta regresiva (solo si hay fecha de evento)
        if (CFG.fechaEvento) {
            var meta = new Date(CFG.fechaEvento + 'T00:00:00').getTime();
            var caja = $('#splash-cuenta');
            var pintar = function () {
                var d = meta - Date.now();
                if (d <= 0) { caja.hidden = true; return; }
                var seg = Math.floor(d / 1000);
                $('#sc-d').textContent = Math.floor(seg / 86400);
                $('#sc-h').textContent = ('0' + Math.floor(seg % 86400 / 3600)).slice(-2);
                $('#sc-m').textContent = ('0' + Math.floor(seg % 3600 / 60)).slice(-2);
                $('#sc-s').textContent = ('0' + (seg % 60)).slice(-2);
                caja.hidden = false;
            };
            pintar();
            setInterval(pintar, 1000);
        }
    }

    iniciarSplash();

    iniciarPortada();
    iniciarProyecto();
})();
