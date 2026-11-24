<?php
/* Funciones comunes: sesión, seguridad (CSRF), ajustes, subida de imágenes, estrellas. */

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(0, '/', '', false, true);
    session_start();
}

date_default_timezone_set('America/Guayaquil');

function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function redirigir($url) {
    header('Location: ' . $url);
    exit;
}

/* ---------- mensajes flash ---------- */
function flash($tipo = null, $texto = null) {
    if ($tipo !== null) {
        $_SESSION['flash'][] = array($tipo, $texto);
        return;
    }
    $f = isset($_SESSION['flash']) ? $_SESSION['flash'] : array();
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */
function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_campo() {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verificar() {
    $t = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    if (!$t || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
        http_response_code(400);
        die('Solicitud no válida. Vuelve atrás y recarga la página.');
    }
}

/* ---------- usuario y roles ---------- */
function usuario_actual() {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $u = fila('SELECT * FROM usuarios WHERE id = ? AND activo = 1', array($_SESSION['uid']));
        }
    }
    return $u;
}

function es_admin() {
    $u = usuario_actual();
    return $u && $u['rol'] === 'admin';
}

function requiere_login() {
    if (!usuario_actual()) {
        redirigir('login.php');
    }
}

function requiere_admin() {
    requiere_login();
    if (!es_admin()) {
        http_response_code(403);
        die('Solo el administrador puede entrar aquí.');
    }
}

/* ---------- ajustes (clave/valor) ---------- */
function ajuste($clave, $defecto = '') {
    $r = fila('SELECT valor FROM ajustes WHERE clave = ?', array($clave));
    return $r ? $r['valor'] : $defecto;
}

function set_ajuste($clave, $valor) {
    if (fila('SELECT clave FROM ajustes WHERE clave = ?', array($clave))) {
        q('UPDATE ajustes SET valor = ? WHERE clave = ?', array($valor, $clave));
    } else {
        q('INSERT INTO ajustes (clave, valor) VALUES (?, ?)', array($clave, $valor));
    }
}

/* ¿Pueden los expositores cargar publicaciones ahora? (lo habilita el administrador) */
function carga_habilitada() {
    return ajuste('carga_habilitada', '0') === '1';
}

/* ---------- fechas ---------- */
function fecha_bonita($ymd) {
    $meses = array('', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
                   'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre');
    $t = strtotime($ymd);
    if (!$t) {
        return $ymd;
    }
    return date('j', $t) . ' de ' . $meses[(int)date('n', $t)] . ' de ' . date('Y', $t);
}

/* ---------- estrellas ---------- */
function estrellas_html($promedio) {
    $html = '<span class="estrellas" aria-label="' . e(number_format($promedio, 1)) . ' de 5">';
    for ($i = 1; $i <= 5; $i++) {
        if ($promedio >= $i - 0.25) {
            $html .= '<span class="e llena">&#9733;</span>';
        } elseif ($promedio >= $i - 0.75) {
            $html .= '<span class="e media">&#9733;</span>';
        } else {
            $html .= '<span class="e">&#9733;</span>';
        }
    }
    return $html . '</span>';
}

/* ---------- subida de imágenes ---------- */
/*
 * Valida que sea una imagen real (jpg/png/webp), la reduce con GD si está disponible
 * (esto además elimina cualquier código escondido en el archivo) y la guarda con nombre aleatorio.
 * Devuelve el nombre del archivo guardado o lanza Exception con el motivo.
 */
function guardar_imagen($f) {
    $cfg = config();
    if (!isset($f['error']) || $f['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se pudo recibir una de las fotos (puede ser muy pesada).');
    }
    if ($f['size'] > $cfg['max_foto_mb'] * 1024 * 1024) {
        throw new Exception('Cada foto debe pesar máximo ' . $cfg['max_foto_mb'] . ' MB.');
    }
    $info = @getimagesize($f['tmp_name']);
    if (!$info) {
        throw new Exception('Uno de los archivos no es una imagen válida.');
    }
    $tipos = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp');
    if (!isset($tipos[$info[2]])) {
        throw new Exception('Solo se aceptan fotos JPG, PNG o WEBP.');
    }
    $ext = $tipos[$info[2]];
    $nombre = date('Ymd') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $destino = __DIR__ . '/../uploads/' . $nombre;

    $hecho = false;
    if (function_exists('imagecreatefromstring')) {
        $img = @imagecreatefromstring(file_get_contents($f['tmp_name']));
        if ($img) {
            $w = imagesx($img);
            $h = imagesy($img);
            $max = $cfg['max_lado_px'];
            if (max($w, $h) > $max) {
                $r = $max / max($w, $h);
                $nw = (int)round($w * $r);
                $nh = (int)round($h * $r);
                $nuevo = imagecreatetruecolor($nw, $nh);
                if ($ext === 'png' || $ext === 'webp') {
                    imagealphablending($nuevo, false);
                    imagesavealpha($nuevo, true);
                }
                imagecopyresampled($nuevo, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagedestroy($img);
                $img = $nuevo;
            }
            if ($ext === 'jpg') {
                $hecho = imagejpeg($img, $destino, 85);
            } elseif ($ext === 'png') {
                $hecho = imagepng($img, $destino, 6);
            } elseif (function_exists('imagewebp')) {
                $hecho = imagewebp($img, $destino, 85);
            }
            imagedestroy($img);
        }
    }
    if (!$hecho && !move_uploaded_file($f['tmp_name'], $destino)) {
        throw new Exception('No se pudo guardar la foto en el servidor.');
    }
    return $nombre;
}

function borrar_imagen($nombre) {
    if (!$nombre || basename($nombre) !== $nombre) {
        return;
    }
    $ruta = __DIR__ . '/../uploads/' . $nombre;
    if (is_file($ruta)) {
        @unlink($ruta);
    }
}

/* Reordena $_FILES['campo'] de múltiples archivos a una lista simple. */
function archivos_subidos($campo) {
    $lista = array();
    if (empty($_FILES[$campo]) || !is_array($_FILES[$campo]['name'])) {
        return $lista;
    }
    $n = count($_FILES[$campo]['name']);
    for ($i = 0; $i < $n; $i++) {
        if ($_FILES[$campo]['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $lista[] = array(
            'name'     => $_FILES[$campo]['name'][$i],
            'tmp_name' => $_FILES[$campo]['tmp_name'][$i],
            'error'    => $_FILES[$campo]['error'][$i],
            'size'     => $_FILES[$campo]['size'][$i],
        );
    }
    return $lista;
}

/* Huella del visitante (para no votar dos veces el mismo proyecto). */
function huella_visitante() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    return hash('sha256', 'revista-speed|' . $ip . '|' . $ua);
}

function video_embed($url) {
    if (!$url) {
        return '';
    }
    if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{11})~', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    return '';
}
