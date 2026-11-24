<?php
require_once __DIR__ . '/includes/funciones.php';
requiere_admin();
$yo = usuario_actual();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $accion = $_POST['accion'];

    if ($accion === 'carga') {
        $nuevo = carga_habilitada() ? '0' : '1';
        set_ajuste('carga_habilitada', $nuevo);
        flash('ok', $nuevo === '1' ? 'Carga habilitada: los expositores ya pueden publicar.' : 'Carga cerrada.');

    } elseif ($accion === 'crear_usuario') {
        $usuario = trim($_POST['usuario']);
        $nombre = trim($_POST['nombre']);
        $curso = trim($_POST['curso']);
        $clave = $_POST['clave'];
        if ($usuario === '' || $nombre === '' || strlen($clave) < 6) {
            flash('error', 'Completa usuario y nombre. La contraseña debe tener al menos 6 caracteres.');
        } elseif (fila('SELECT id FROM usuarios WHERE usuario = ?', array($usuario))) {
            flash('error', 'Ese usuario ya existe.');
        } else {
            q('INSERT INTO usuarios (usuario, clave_hash, nombre, curso, rol, activo, creado) VALUES (?,?,?,?,?,1,?)',
              array($usuario, password_hash($clave, PASSWORD_DEFAULT), $nombre, $curso, 'expositor', date('Y-m-d H:i:s')));
            flash('ok', 'Usuario creado: ' . $usuario);
        }

    } elseif ($accion === 'activar') {
        $uid = (int)$_POST['id'];
        if ($uid !== (int)$yo['id']) {
            q('UPDATE usuarios SET activo = 1 - activo WHERE id = ?', array($uid));
            flash('ok', 'Usuario actualizado.');
        }

    } elseif ($accion === 'clave') {
        $uid = (int)$_POST['id'];
        $clave = $_POST['clave'];
        if (strlen($clave) < 6) {
            flash('error', 'La nueva contraseña debe tener al menos 6 caracteres.');
        } else {
            q('UPDATE usuarios SET clave_hash = ? WHERE id = ?', array(password_hash($clave, PASSWORD_DEFAULT), $uid));
            flash('ok', 'Contraseña cambiada.');
        }

    } elseif ($accion === 'borrar_resena') {
        q('DELETE FROM valoraciones WHERE id = ?', array((int)$_POST['id']));
        flash('ok', 'Reseña eliminada.');
    }
    redirigir('admin.php');
}

$abierta = carga_habilitada();
$usuarios = filas('SELECT * FROM usuarios ORDER BY rol, usuario');
$resenas = filas('SELECT v.*, p.titulo FROM valoraciones v JOIN proyectos p ON p.id = v.proyecto_id ORDER BY v.fecha DESC LIMIT 40');
$cont = fila("SELECT
    (SELECT COUNT(*) FROM proyectos WHERE estado = 'aprobado') AS pub,
    (SELECT COUNT(*) FROM proyectos WHERE estado = 'pendiente') AS pend,
    (SELECT COUNT(*) FROM valoraciones) AS votos");

$titulo = 'Administración';
require __DIR__ . '/includes/cabecera.php';
?>
<h1>Administración</h1>

<div class="resumen">
    <div><strong><?= (int)$cont['pub'] ?></strong><span>publicados</span></div>
    <div><strong><?= (int)$cont['pend'] ?></strong><span>pendientes de aprobar</span></div>
    <div><strong><?= (int)$cont['votos'] ?></strong><span>calificaciones</span></div>
</div>

<section class="caja">
    <h2>Módulo de carga de publicaciones</h2>
    <p>Estado: <span class="estado <?= $abierta ? 'estado-aprobado' : 'estado-pendiente' ?>"><?= $abierta ? 'HABILITADO' : 'CERRADO' ?></span></p>
    <p class="tenue">Cuando está habilitado, los expositores con cuenta pueden crear y editar sus proyectos (quedan pendientes hasta que tú los publiques desde "Mi panel"). Ciérralo cuando termine el plazo.</p>
    <form method="post"><?= csrf_campo() ?>
        <button class="<?= $abierta ? 'btn-peligro' : 'btn' ?>" name="accion" value="carga"><?= $abierta ? 'Cerrar la carga' : 'Habilitar la carga' ?></button>
    </form>
    <p><a href="panel.php">Ir a aprobar proyectos pendientes &rarr;</a></p>
</section>

<section class="caja">
    <h2>Crear cuenta de expositor / curso</h2>
    <form method="post" class="form form-ancho" autocomplete="off">
        <?= csrf_campo() ?><input type="hidden" name="accion" value="crear_usuario">
        <div class="dos-col">
            <label>Usuario <input name="usuario" required></label>
            <label>Contraseña inicial <input name="clave" required minlength="6"></label>
        </div>
        <div class="dos-col">
            <label>Nombre del responsable <input name="nombre" required></label>
            <label>Curso <input name="curso" placeholder="Ej. 3.º de Bachillerato B"></label>
        </div>
        <button class="btn" type="submit">Crear cuenta</button>
    </form>
</section>

<section class="caja">
    <h2>Cuentas</h2>
    <div class="tabla-envoltura">
    <table class="tabla">
        <thead><tr><th>Usuario</th><th>Nombre</th><th>Curso</th><th>Rol</th><th>Nueva contraseña</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($usuarios as $x): ?>
            <tr>
                <td data-t="Usuario"><?= e($x['usuario']) ?><?= $x['activo'] ? '' : ' <small class="tenue">(desactivado)</small>' ?></td>
                <td data-t="Nombre"><?= e($x['nombre']) ?></td>
                <td data-t="Curso"><?= e($x['curso']) ?></td>
                <td data-t="Rol"><?= e($x['rol']) ?></td>
                <td data-t="Contraseña">
                    <form method="post" class="en-linea"><?= csrf_campo() ?><input type="hidden" name="accion" value="clave"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
                        <input name="clave" placeholder="nueva" minlength="6" required style="width:7rem"> <button class="btn-sec">Cambiar</button></form>
                </td>
                <td class="acciones">
                    <?php if ((int)$x['id'] !== (int)$yo['id']): ?>
                    <form method="post" class="en-linea"><?= csrf_campo() ?><input type="hidden" name="accion" value="activar"><input type="hidden" name="id" value="<?= (int)$x['id'] ?>">
                        <button class="btn-sec"><?= $x['activo'] ? 'Desactivar' : 'Activar' ?></button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>

<section class="caja">
    <h2>Moderar reseñas (últimas 40)</h2>
    <?php if (!$resenas): ?><p class="tenue">Aún no hay reseñas.</p><?php endif; ?>
    <ul class="lista-resenas">
    <?php foreach ($resenas as $r): ?>
        <li>
            <div><?= estrellas_html((float)$r['estrellas']) ?> <strong><?= e($r['nombre'] !== '' ? $r['nombre'] : 'Visitante') ?></strong>
                en <a href="proyecto.php?id=<?= (int)$r['proyecto_id'] ?>"><?= e($r['titulo']) ?></a></div>
            <?php if ($r['comentario'] !== ''): ?><p><?= e($r['comentario']) ?></p><?php endif; ?>
            <form method="post" class="en-linea" data-confirmar="¿Eliminar esta reseña?"><?= csrf_campo() ?>
                <input type="hidden" name="accion" value="borrar_resena"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn-peligro">Eliminar</button></form>
        </li>
    <?php endforeach; ?>
    </ul>
</section>
<?php require __DIR__ . '/includes/pie.php'; ?>
