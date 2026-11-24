<?php
/*
 * INSTALADOR: se abre UNA sola vez en el navegador (https://tusitio.com/install.php).
 * Crea las tablas y el usuario administrador. Después de instalar, BORRA este archivo del hosting.
 */
require_once __DIR__ . '/includes/funciones.php';

$error = '';
$listo = false;
$yaInstalado = false;

try {
    crear_tablas();
    $r = fila('SELECT COUNT(*) AS n FROM usuarios');
    $yaInstalado = ($r['n'] > 0);
} catch (Exception $ex) {
    $error = 'No se pudo preparar la base de datos: ' . $ex->getMessage();
}

if (!$error && !$yaInstalado && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $usuario = trim($_POST['usuario']);
    $nombre  = trim($_POST['nombre']);
    $clave   = $_POST['clave'];
    if ($usuario === '' || $nombre === '' || strlen($clave) < 8) {
        $error = 'Completa los datos. La contraseña debe tener al menos 8 caracteres.';
    } else {
        q('INSERT INTO usuarios (usuario, clave_hash, nombre, curso, rol, activo, creado) VALUES (?,?,?,?,?,1,?)',
          array($usuario, password_hash($clave, PASSWORD_DEFAULT), $nombre, 'Organización', 'admin', date('Y-m-d H:i:s')));
        set_ajuste('carga_habilitada', '1');
        $listo = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalar · Revista Speed</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body><main class="contenedor estrecho">
<h1>Instalar Revista Speed</h1>
<?php if ($error): ?><div class="aviso aviso-error"><?= e($error) ?></div><?php endif; ?>

<?php if ($listo): ?>
    <div class="aviso aviso-ok">Listo. La base de datos está creada y el administrador registrado.</div>
    <p><strong>Importante:</strong> borra el archivo <code>install.php</code> de tu hosting ahora.</p>
    <p><a class="btn" href="login.php">Ir a ingresar</a></p>
<?php elseif ($yaInstalado): ?>
    <div class="aviso aviso-ok">Ya está instalado. Borra <code>install.php</code> del hosting por seguridad.</div>
    <p><a class="btn" href="index.php">Ir a la revista</a></p>
<?php elseif (!$error): ?>
    <p>Crea la cuenta del <strong>administrador</strong> (la que habilita la carga de publicaciones y modera).</p>
    <form method="post" class="form">
        <?= csrf_campo() ?>
        <label>Nombre completo <input name="nombre" required></label>
        <label>Usuario <input name="usuario" required autocomplete="off"></label>
        <label>Contraseña (mínimo 8 caracteres) <input type="password" name="clave" required minlength="8" autocomplete="new-password"></label>
        <button class="btn" type="submit">Instalar</button>
    </form>
<?php endif; ?>
</main></body></html>
