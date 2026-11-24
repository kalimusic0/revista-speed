<?php
/* Cabecera común. Antes de incluirla define $titulo (opcional). */
require_once __DIR__ . '/funciones.php';
$cfg = config();
$u = usuario_actual();
$tituloPagina = isset($titulo) ? $titulo . ' · ' . $cfg['sitio']['nombre'] : $cfg['sitio']['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($tituloPagina) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="cab">
    <div class="cab-in">
        <a class="marca" href="index.php"><span class="marca-r">SPEED</span> <span class="marca-t">Revista</span></a>
        <nav class="menu">
            <a href="index.php">Proyectos</a>
            <?php if ($u): ?>
                <a href="panel.php">Mi panel</a>
                <?php if ($u['rol'] === 'admin'): ?><a href="admin.php">Administración</a><?php endif; ?>
                <a href="logout.php" class="btn-sec">Salir (<?= e($u['usuario']) ?>)</a>
            <?php else: ?>
                <a href="login.php" class="btn-sec">Ingresar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="contenedor">
<?php foreach (flash() as $f): ?>
    <div class="aviso aviso-<?= e($f[0]) ?>"><?= e($f[1]) ?></div>
<?php endforeach; ?>
