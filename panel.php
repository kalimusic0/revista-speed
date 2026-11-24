<?php
require_once __DIR__ . '/includes/funciones.php';
requiere_login();
$u = usuario_actual();

/* ---------- acciones sobre un proyecto ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $pid = (int)$_POST['id'];
    $p = fila('SELECT * FROM proyectos WHERE id = ?', array($pid));
    $esDueno = $p && (int)$p['usuario_id'] === (int)$u['id'];
    $accion = $_POST['accion'];

    if ($p && ($accion === 'aprobar' || $accion === 'ocultar') && es_admin()) {
        q('UPDATE proyectos SET estado = ? WHERE id = ?', array($accion === 'aprobar' ? 'aprobado' : 'pendiente', $pid));
        flash('ok', $accion === 'aprobar' ? 'Proyecto publicado.' : 'Proyecto ocultado.');
    } elseif ($p && $accion === 'eliminar' && ($esDueno || es_admin())) {
        if (!es_admin() && !carga_habilitada()) {
            flash('error', 'La carga de publicaciones está cerrada.');
        } else {
            foreach (filas('SELECT archivo FROM imagenes WHERE proyecto_id = ?', array($pid)) as $im) {
                borrar_imagen($im['archivo']);
            }
            borrar_imagen($p['imagen_principal']);
            q('DELETE FROM imagenes WHERE proyecto_id = ?', array($pid));
            q('DELETE FROM valoraciones WHERE proyecto_id = ?', array($pid));
            q('DELETE FROM proyectos WHERE id = ?', array($pid));
            flash('ok', 'Proyecto eliminado.');
        }
    }
    redirigir('panel.php');
}

if (es_admin()) {
    $mis = filas('SELECT p.*, u.usuario AS autor FROM proyectos p JOIN usuarios u ON u.id = p.usuario_id ORDER BY p.estado, p.fecha DESC');
} else {
    $mis = filas('SELECT p.*, u.usuario AS autor FROM proyectos p JOIN usuarios u ON u.id = p.usuario_id WHERE p.usuario_id = ? ORDER BY p.fecha DESC', array($u['id']));
}
$abierta = carga_habilitada();

$titulo = 'Mi panel';
require __DIR__ . '/includes/cabecera.php';
?>
<div class="panel-cab">
    <div>
        <h1>Mi panel</h1>
        <p class="tenue">Hola, <?= e($u['nombre']) ?><?= $u['curso'] ? ' · ' . e($u['curso']) : '' ?></p>
    </div>
    <?php if ($abierta || es_admin()): ?>
        <a class="btn" href="editar.php">+ Nueva publicación</a>
    <?php endif; ?>
</div>

<?php if (!$abierta): ?>
    <div class="aviso aviso-info">
        <?= es_admin() ? 'La carga para los expositores está <strong>cerrada</strong>. Puedes abrirla en Administración.' : 'La carga de publicaciones está <strong>cerrada</strong> por ahora. El administrador la habilitará cuando corresponda.' ?>
    </div>
<?php endif; ?>

<?php if (!$mis): ?>
    <p class="vacio">Todavía no hay publicaciones<?= es_admin() ? '' : ' tuyas' ?>.</p>
<?php else: ?>
<div class="tabla-envoltura">
<table class="tabla">
    <thead><tr><th>Proyecto</th><th>Curso</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($mis as $p): ?>
        <tr>
            <td data-t="Proyecto"><a href="proyecto.php?id=<?= (int)$p['id'] ?>"><strong><?= e($p['titulo']) ?></strong></a>
                <?php if (es_admin()): ?><br><small class="tenue">por <?= e($p['autor']) ?></small><?php endif; ?></td>
            <td data-t="Curso"><?= e($p['curso']) ?></td>
            <td data-t="Fecha"><?= e($p['fecha']) ?></td>
            <td data-t="Estado"><span class="estado estado-<?= e($p['estado']) ?>"><?= $p['estado'] === 'aprobado' ? 'Publicado' : 'Pendiente' ?></span></td>
            <td class="acciones">
                <?php if ($abierta || es_admin()): ?><a class="btn-sec" href="editar.php?id=<?= (int)$p['id'] ?>">Editar</a><?php endif; ?>
                <?php if (es_admin()): ?>
                    <form method="post" class="en-linea"><?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <?php if ($p['estado'] === 'aprobado'): ?>
                            <button class="btn-sec" name="accion" value="ocultar">Ocultar</button>
                        <?php else: ?>
                            <button class="btn" name="accion" value="aprobar">Publicar</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
                <?php if ($abierta || es_admin()): ?>
                <form method="post" class="en-linea" data-confirmar="¿Eliminar este proyecto y sus fotos? No se puede deshacer.">
                    <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="btn-peligro" name="accion" value="eliminar">Eliminar</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/pie.php'; ?>
