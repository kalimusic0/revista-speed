<?php
require_once __DIR__ . '/includes/funciones.php';
requiere_login();
$u = usuario_actual();

if (!carga_habilitada() && !es_admin()) {
    flash('error', 'La carga de publicaciones está cerrada.');
    redirigir('panel.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$p = null;
if ($id) {
    $p = fila('SELECT * FROM proyectos WHERE id = ?', array($id));
    if (!$p || (!es_admin() && (int)$p['usuario_id'] !== (int)$u['id'])) {
        flash('error', 'No puedes editar ese proyecto.');
        redirigir('panel.php');
    }
}

$err = array();
$d = array(
    'titulo' => '', 'curso' => $u['curso'], 'expositores' => '', 'resena' => '', 'descripcion' => '',
    'innovacion' => '', 'stand' => '', 'video_url' => '', 'fecha' => date('Y-m-d'),
);
if ($p) {
    foreach ($d as $k => $v) {
        $d[$k] = $p[$k];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    foreach ($d as $k => $v) {
        $d[$k] = isset($_POST[$k]) ? trim($_POST[$k]) : '';
    }
    $d['resena'] = mb_substr($d['resena'], 0, 280);

    foreach (array('titulo' => 'el título', 'curso' => 'el curso', 'expositores' => 'los expositores',
                   'resena' => 'la reseña corta', 'descripcion' => 'la descripción', 'innovacion' => 'la idea innovadora') as $k => $n) {
        if ($d[$k] === '') {
            $err[] = 'Falta completar ' . $n . '.';
        }
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d['fecha'])) {
        $err[] = 'La fecha no es válida.';
    }
    if ($d['video_url'] !== '' && !preg_match('~^https?://~i', $d['video_url'])) {
        $err[] = 'El enlace del video debe empezar con http:// o https://';
    }

    $nuevas = array();
    $portada = null;
    if (!$err) {
        try {
            if (!empty($_FILES['portada']) && $_FILES['portada']['error'] !== UPLOAD_ERR_NO_FILE) {
                $portada = guardar_imagen($_FILES['portada']);
            }
            foreach (archivos_subidos('fotos') as $f) {
                $nuevas[] = guardar_imagen($f);
            }
        } catch (Exception $ex) {
            $err[] = $ex->getMessage();
        }
        if (!$p && !$portada && !$err) {
            $err[] = 'Sube una imagen principal para la tarjeta del proyecto.';
        }
    }

    if (!$err) {
        $ahora = date('Y-m-d H:i:s');
        if ($p) {
            q('UPDATE proyectos SET titulo=?, curso=?, expositores=?, resena=?, descripcion=?, innovacion=?, stand=?, video_url=?, fecha=? WHERE id=?',
              array($d['titulo'], $d['curso'], $d['expositores'], $d['resena'], $d['descripcion'], $d['innovacion'], $d['stand'], $d['video_url'], $d['fecha'], $id));
            if ($portada) {
                borrar_imagen($p['imagen_principal']);
                q('UPDATE proyectos SET imagen_principal = ? WHERE id = ?', array($portada, $id));
            }
            // quitar fotos marcadas
            if (!empty($_POST['quitar']) && is_array($_POST['quitar'])) {
                foreach ($_POST['quitar'] as $qid) {
                    $im = fila('SELECT * FROM imagenes WHERE id = ? AND proyecto_id = ?', array((int)$qid, $id));
                    if ($im) {
                        borrar_imagen($im['archivo']);
                        q('DELETE FROM imagenes WHERE id = ?', array($im['id']));
                    }
                }
            }
        } else {
            // los proyectos del administrador se publican directo; los de expositores quedan pendientes
            $estado = es_admin() ? 'aprobado' : 'pendiente';
            q('INSERT INTO proyectos (usuario_id, titulo, curso, expositores, resena, descripcion, innovacion, stand, video_url, imagen_principal, estado, fecha, creado) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
              array($u['id'], $d['titulo'], $d['curso'], $d['expositores'], $d['resena'], $d['descripcion'], $d['innovacion'], $d['stand'], $d['video_url'], $portada, $estado, $d['fecha'], $ahora));
            $id = (int)db()->lastInsertId();
        }
        foreach ($nuevas as $n) {
            q('INSERT INTO imagenes (proyecto_id, archivo) VALUES (?, ?)', array($id, $n));
        }
        flash('ok', $p ? 'Cambios guardados.' : (es_admin() ? 'Proyecto publicado.' : 'Proyecto enviado. Aparecerá en la revista cuando el administrador lo apruebe.'));
        redirigir('panel.php');
    } else {
        // si falló algo, no dejar fotos huérfanas
        foreach ($nuevas as $n) { borrar_imagen($n); }
        if ($portada) { borrar_imagen($portada); }
    }
}

$fotos = $p ? filas('SELECT * FROM imagenes WHERE proyecto_id = ? ORDER BY id', array($id)) : array();
$titulo = $p ? 'Editar proyecto' : 'Nueva publicación';
require __DIR__ . '/includes/cabecera.php';
?>
<p class="migas"><a href="panel.php">&larr; Mi panel</a></p>
<h1><?= e($titulo) ?></h1>

<?php foreach ($err as $m): ?><div class="aviso aviso-error"><?= e($m) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="form form-ancho">
    <?= csrf_campo() ?>
    <label>Título del proyecto
        <input name="titulo" maxlength="120" required value="<?= e($d['titulo']) ?>"></label>

    <div class="dos-col">
        <label>Curso
            <input name="curso" maxlength="80" required value="<?= e($d['curso']) ?>" placeholder="Ej. 2.º de Bachillerato A"></label>
        <label>Stand / ubicación (opcional)
            <input name="stand" maxlength="60" value="<?= e($d['stand']) ?>" placeholder="Ej. Pasillo 2, mesa 5"></label>
    </div>

    <label>Expositores (uno por línea)
        <textarea name="expositores" rows="3" required placeholder="Nombre Apellido&#10;Nombre Apellido&#10;Docente tutor: …"><?= e($d['expositores']) ?></textarea></label>

    <label>Reseña corta <small class="tenue">(sale en la tarjeta de la portada, máx. 280 caracteres)</small>
        <textarea name="resena" rows="2" maxlength="280" required><?= e($d['resena']) ?></textarea></label>

    <label>Descripción completa <small class="tenue">(qué es, qué problema resuelve, cómo funciona)</small>
        <textarea name="descripcion" rows="7" required><?= e($d['descripcion']) ?></textarea></label>

    <label>¿Qué tiene de innovador?
        <textarea name="innovacion" rows="4" required><?= e($d['innovacion']) ?></textarea></label>

    <div class="dos-col">
        <label>Fecha de publicación
            <input type="date" name="fecha" required value="<?= e($d['fecha']) ?>"></label>
        <label>Video de YouTube (opcional)
            <input name="video_url" maxlength="190" value="<?= e($d['video_url']) ?>" placeholder="https://youtu.be/…"></label>
    </div>

    <fieldset>
        <legend>Imagen principal <?= $p ? '(déjala vacía para conservar la actual)' : '' ?></legend>
        <?php if ($p && $p['imagen_principal']): ?>
            <img class="mini" src="uploads/<?= e($p['imagen_principal']) ?>" alt="Imagen principal actual">
        <?php endif; ?>
        <input type="file" name="portada" accept="image/jpeg,image/png,image/webp" <?= $p ? '' : 'required' ?>>
        <small class="tenue">Foto horizontal, JPG/PNG/WEBP, máx. <?= (int)$cfg['max_foto_mb'] ?> MB.</small>
    </fieldset>

    <fieldset>
        <legend>Evidencias fotográficas</legend>
        <?php if ($fotos): ?>
            <div class="mini-fila">
                <?php foreach ($fotos as $f): ?>
                    <label class="mini-item"><img class="mini" src="uploads/<?= e($f['archivo']) ?>" alt="">
                        <span><input type="checkbox" name="quitar[]" value="<?= (int)$f['id'] ?>"> Quitar</span></label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <input type="file" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple>
        <small class="tenue">Puedes elegir varias fotos a la vez.</small>
    </fieldset>

    <button class="btn" type="submit"><?= $p ? 'Guardar cambios' : 'Enviar publicación' ?></button>
</form>
<?php require __DIR__ . '/includes/pie.php'; ?>
