<?php
require_once __DIR__ . '/includes/funciones.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$p = fila('SELECT * FROM proyectos WHERE id = ?', array($id));
$u = usuario_actual();
$puedeVer = $p && ($p['estado'] === 'aprobado' || ($u && (es_admin() || (int)$p['usuario_id'] === (int)$u['id'])));
if (!$puedeVer) {
    http_response_code(404);
    $titulo = 'No encontrado';
    require __DIR__ . '/includes/cabecera.php';
    echo '<p class="vacio">Este proyecto no existe o todavía no está publicado.</p><p><a class="btn" href="index.php">Volver</a></p>';
    require __DIR__ . '/includes/pie.php';
    exit;
}

/* ---------- guardar calificación + reseña ---------- */
$cookie = 'rv_' . $id;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'valorar') {
    csrf_verificar();
    $est = (int)$_POST['estrellas'];
    $nom = trim(mb_substr($_POST['nombre'], 0, 60));
    $com = trim(mb_substr($_POST['comentario'], 0, 300));
    if ($p['estado'] !== 'aprobado') {
        flash('error', 'Solo se pueden calificar proyectos publicados.');
    } elseif ($est < 1 || $est > 5) {
        flash('error', 'Elige de 1 a 5 estrellas.');
    } elseif (isset($_COOKIE[$cookie])) {
        flash('error', 'Ya calificaste este proyecto desde este navegador. ¡Gracias!');
    } else {
        try {
            q('INSERT INTO valoraciones (proyecto_id, nombre, estrellas, comentario, huella, fecha) VALUES (?,?,?,?,?,?)',
              array($id, $nom, $est, $com, huella_visitante(), date('Y-m-d H:i:s')));
            setcookie($cookie, '1', time() + 86400 * 90, '/');
            flash('ok', '¡Gracias por tu calificación!');
        } catch (Exception $ex) {
            flash('error', 'Ya se registró una calificación tuya para este proyecto.');
        }
    }
    redirigir('proyecto.php?id=' . $id . '#resenas');
}

$titulo = $p['titulo'];
require __DIR__ . '/includes/cabecera.php';

$imagenes = filas('SELECT * FROM imagenes WHERE proyecto_id = ? ORDER BY id', array($id));
$agr = fila('SELECT AVG(estrellas) AS promedio, COUNT(*) AS votos FROM valoraciones WHERE proyecto_id = ?', array($id));
$resenas = filas('SELECT * FROM valoraciones WHERE proyecto_id = ? ORDER BY fecha DESC', array($id));
$yaVoto = isset($_COOKIE[$cookie]);
$embed = video_embed($p['video_url']);
?>
<p class="migas"><a href="index.php">&larr; Todos los proyectos</a></p>

<?php if ($p['estado'] !== 'aprobado'): ?>
    <div class="aviso aviso-info">Vista previa: este proyecto está <strong>pendiente de aprobación</strong> y el público todavía no lo ve.</div>
<?php endif; ?>

<article class="articulo">
    <header class="art-cab">
        <p class="meta"><span class="etq"><?= e($p['curso']) ?></span> <time><?= e(fecha_bonita($p['fecha'])) ?></time>
            <?php if ($p['stand']): ?> · Stand: <strong><?= e($p['stand']) ?></strong><?php endif; ?></p>
        <h1><?= e($p['titulo']) ?></h1>
        <p class="lead"><?= e($p['resena']) ?></p>
        <p class="art-nota">
            <?php if ($agr['votos'] > 0): ?>
                <?= estrellas_html((float)$agr['promedio']) ?> <strong><?= number_format($agr['promedio'], 1) ?></strong> <small>(<?= (int)$agr['votos'] ?> calificaciones)</small>
            <?php else: ?>
                <small class="tenue">Aún sin calificaciones</small>
            <?php endif; ?>
        </p>
    </header>

    <?php if ($p['imagen_principal']): ?>
        <figure class="art-portada"><img src="uploads/<?= e($p['imagen_principal']) ?>" alt="<?= e($p['titulo']) ?>"></figure>
    <?php endif; ?>

    <div class="art-cols">
        <div class="art-texto">
            <h2>Sobre el proyecto</h2>
            <p><?= nl2br(e($p['descripcion'])) ?></p>

            <h2>¿Qué tiene de innovador?</h2>
            <p><?= nl2br(e($p['innovacion'])) ?></p>

            <?php if ($embed): ?>
                <h2>Video</h2>
                <div class="video"><iframe src="<?= e($embed) ?>" allowfullscreen loading="lazy" title="Video del proyecto"></iframe></div>
            <?php elseif ($p['video_url']): ?>
                <p><a class="btn-sec" href="<?= e($p['video_url']) ?>" target="_blank" rel="noopener">Ver video del proyecto</a></p>
            <?php endif; ?>
        </div>
        <aside class="ficha">
            <h3>Expositores</h3>
            <p><?= nl2br(e($p['expositores'])) ?></p>
            <h3>Curso</h3>
            <p><?= e($p['curso']) ?></p>
            <?php if ($p['stand']): ?><h3>Ubicación</h3><p><?= e($p['stand']) ?></p><?php endif; ?>
        </aside>
    </div>

    <?php if ($imagenes): ?>
    <section>
        <h2>Evidencias fotográficas</h2>
        <div class="galeria">
            <?php foreach ($imagenes as $im): ?>
                <a href="uploads/<?= e($im['archivo']) ?>" class="gal-item" data-lightbox>
                    <img src="uploads/<?= e($im['archivo']) ?>" alt="Evidencia del proyecto <?= e($p['titulo']) ?>" loading="lazy">
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section id="resenas" class="resenas">
        <h2>Califica este proyecto</h2>
        <?php if ($p['estado'] !== 'aprobado'): ?>
            <p class="tenue">Las calificaciones se abren cuando el proyecto sea publicado.</p>
        <?php elseif ($yaVoto): ?>
            <p class="aviso aviso-ok">Ya enviaste tu calificación. ¡Gracias por participar!</p>
        <?php else: ?>
        <form method="post" class="form form-valorar">
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="valorar">
            <div class="estrellas-input" role="radiogroup" aria-label="Calificación">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="st<?= $i ?>" name="estrellas" value="<?= $i ?>" required>
                    <label for="st<?= $i ?>" title="<?= $i ?> estrella<?= $i > 1 ? 's' : '' ?>">&#9733;</label>
                <?php endfor; ?>
            </div>
            <label>Tu nombre (opcional) <input name="nombre" maxlength="60" autocomplete="off"></label>
            <label>Tu reseña corta (opcional, máx. 300 caracteres)
                <textarea name="comentario" rows="3" maxlength="300" placeholder="¿Qué te pareció el proyecto?"></textarea></label>
            <button class="btn" type="submit">Enviar calificación</button>
        </form>
        <?php endif; ?>

        <?php if ($resenas): ?>
        <h3>Reseñas de los visitantes</h3>
        <ul class="lista-resenas">
            <?php foreach ($resenas as $r): ?>
                <li>
                    <div><?= estrellas_html((float)$r['estrellas']) ?> <strong><?= e($r['nombre'] !== '' ? $r['nombre'] : 'Visitante') ?></strong>
                        <small class="tenue"><?= e(fecha_bonita(substr($r['fecha'], 0, 10))) ?></small></div>
                    <?php if ($r['comentario'] !== ''): ?><p><?= nl2br(e($r['comentario'])) ?></p><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>
</article>
<?php require __DIR__ . '/includes/pie.php'; ?>
