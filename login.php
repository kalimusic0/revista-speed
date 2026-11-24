<?php
require_once __DIR__ . '/includes/funciones.php';

if (usuario_actual()) {
    redirigir('panel.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    // freno simple contra adivinar contraseñas: 5 fallos = espera de 60 s
    $bloqueo = isset($_SESSION['bloqueo_hasta']) ? $_SESSION['bloqueo_hasta'] : 0;
    if ($bloqueo > time()) {
        $error = 'Demasiados intentos. Espera un minuto e inténtalo otra vez.';
    } else {
        $usuario = trim($_POST['usuario']);
        $clave = $_POST['clave'];
        $f = fila('SELECT * FROM usuarios WHERE usuario = ? AND activo = 1', array($usuario));
        if ($f && password_verify($clave, $f['clave_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = $f['id'];
            $_SESSION['fallos'] = 0;
            redirigir('panel.php');
        }
        $_SESSION['fallos'] = (isset($_SESSION['fallos']) ? $_SESSION['fallos'] : 0) + 1;
        if ($_SESSION['fallos'] >= 5) {
            $_SESSION['bloqueo_hasta'] = time() + 60;
            $_SESSION['fallos'] = 0;
        }
        $error = 'Usuario o contraseña incorrectos.';
    }
}

$titulo = 'Ingresar';
require __DIR__ . '/includes/cabecera.php';
?>
<div class="estrecho">
    <h1>Ingresar</h1>
    <p class="tenue">Para los expositores y organizadores de la casa abierta. Si eres visitante no necesitas cuenta.</p>
    <?php if ($error): ?><div class="aviso aviso-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form" autocomplete="off">
        <?= csrf_campo() ?>
        <label>Usuario <input name="usuario" required autofocus></label>
        <label>Contraseña <input type="password" name="clave" required></label>
        <button class="btn" type="submit">Entrar</button>
    </form>
</div>
<?php require __DIR__ . '/includes/pie.php'; ?>
