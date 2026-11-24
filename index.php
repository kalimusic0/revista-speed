<?php
$titulo = 'Proyectos';
require __DIR__ . '/includes/cabecera.php';

/* ---------- filtros ---------- */
$curso = isset($_GET['curso']) ? trim($_GET['curso']) : '';
$buscar = isset($_GET['q']) ? trim($_GET['q']) : '';
$orden = (isset($_GET['orden']) && $_GET['orden'] === 'recientes') ? 'recientes' : 'antiguas';

$where = "p.estado = 'aprobado'";
$par = array();
if ($curso !== '') {
    $where .= ' AND p.curso = ?';
    $par[] = $curso;
}
if ($buscar !== '') {
    $where .= ' AND (p.titulo LIKE ? OR p.resena LIKE ? OR p.expositores LIKE ?)';
    $par[] = "%$buscar%";
    $par[] = "%$buscar%";
    $par[] = "%$buscar%";
}
$dir = ($orden === 'recientes') ? 'DESC' : 'ASC';

$proyectos = filas(
    "SELECT p.*,
        (SELECT AVG(v.estrellas) FROM valoraciones v WHERE v.proyecto_id = p.id) AS promedio,
        (SELECT COUNT(*) FROM valoraciones v WHERE v.proyecto_id = p.id) AS votos
     FROM proyectos p WHERE $where ORDER BY p.fecha $dir, p.id $dir", $par);

$cursos = filas("SELECT DISTINCT curso FROM proyectos WHERE estado = 'aprobado' ORDER BY curso");

$destacados = filas(
    "SELECT p.id, p.titulo, p.curso, p.imagen_principal,
        AVG(v.estrellas) AS promedio, COUNT(v.id) AS votos
     FROM proyectos p JOIN valoraciones v ON v.proyecto_id = p.id
     WHERE p.estado = 'aprobado'
     GROUP BY p.id, p.titulo, p.curso, p.imagen_principal
     ORDER BY promedio DESC, votos DESC LIMIT 3");

$sitio = $cfg['sitio'];
$diasFaltan = null;
if (!empty($sitio['fecha_evento'])) {
    $diasFaltan = (int)floor((strtotime($sitio['fecha_evento']) - strtotime(date('Y-m-d'))) / 86400);
}
?>
<section class="portada">
    <p class="portada-sup"><?= e($sitio['evento']) ?></p>
    <h1>Proyectos e ideas que <span>van a toda velocidad</span></h1>
    <p class="portada-sub">Conoce lo que construyeron todos los cursos: expositores, ideas innovadoras y evidencias fotográficas. Entra a cada proyecto, califícalo y deja tu reseña.</p>
    <?php if ($diasFaltan !== null && $diasFaltan >= 0): ?>
        <p class="cuenta"><?= $diasFaltan === 0 ? '¡Es hoy!' : 'Faltan ' . $diasFaltan . ' día' . ($diasFaltan === 1 ? '' : 's') ?></p>
    <?php endif; ?>
</section>

<?php if ($destacados): ?>
<section class="destacados">
    <h2>Mejor puntuados</h2>
    <div class="dest-fila">
        <?php foreach ($destacados as $i => $d): ?>
        <a class="dest" href="proyecto.php?id=<?= (int)$d['id'] ?>">
            <span class="dest-n">#<?= $i + 1 ?></span>
            <span class="dest-t"><?= e($d['titulo']) ?></span>
            <span class="dest-c"><?= e($d['curso']) ?></span>
            <?= estrellas_html((float)$d['promedio']) ?> <small><?= number_format($d['promedio'], 1) ?> (<?= (int)$d['votos'] ?>)</small>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section>
    <form class="filtros" method="get">
        <input type="search" name="q" value="<?= e($buscar) ?>" placeholder="Buscar proyecto, tema o expositor…">
        <select name="curso">
            <option value="">Todos los cursos</option>
            <?php foreach ($cursos as $c): ?>
                <option value="<?= e($c['curso']) ?>" <?= $c['curso'] === $curso ? 'selected' : '' ?>><?= e($c['curso']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="orden">
            <option value="antiguas" <?= $orden === 'antiguas' ? 'selected' : '' ?>>Fecha: de la más antigua</option>
            <option value="recientes" <?= $orden === 'recientes' ? 'selected' : '' ?>>Fecha: la más reciente primero</option>
        </select>
        <button class="btn" type="submit">Filtrar</button>
    </form>

    <?php if (!$proyectos): ?>
        <p class="vacio">Todavía no hay proyectos publicados<?= ($curso || $buscar) ? ' con esos filtros' : '' ?>.</p>
    <?php else: ?>
    <div class="rejilla">
        <?php foreach ($proyectos as $p): ?>
        <article class="tarjeta">
            <a class="tarjeta-img" href="proyecto.php?id=<?= (int)$p['id'] ?>">
                <?php if ($p['imagen_principal']): ?>
                    <img src="uploads/<?= e($p['imagen_principal']) ?>" alt="<?= e($p['titulo']) ?>" loading="lazy">
                <?php else: ?>
                    <span class="sin-img">Sin imagen</span>
                <?php endif; ?>
            </a>
            <div class="tarjeta-cuerpo">
                <p class="meta"><span class="etq"><?= e($p['curso']) ?></span> <time><?= e(fecha_bonita($p['fecha'])) ?></time></p>
                <h3><a href="proyecto.php?id=<?= (int)$p['id'] ?>"><?= e($p['titulo']) ?></a></h3>
                <p class="resena"><?= e($p['resena']) ?></p>
                <div class="tarjeta-pie">
                    <span>
                        <?php if ($p['votos'] > 0): ?>
                            <?= estrellas_html((float)$p['promedio']) ?> <small><?= number_format($p['promedio'], 1) ?> (<?= (int)$p['votos'] ?>)</small>
                        <?php else: ?>
                            <small class="tenue">Sin calificaciones aún</small>
                        <?php endif; ?>
                    </span>
                    <a class="ver-mas" href="proyecto.php?id=<?= (int)$p['id'] ?>">Ver más &rarr;</a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/pie.php'; ?>
