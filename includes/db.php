<?php
/* Conexión a la base de datos (PDO). Funciona igual con SQLite o MySQL. */

function config() {
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/config.php';
    }
    return $cfg;
}

function db_driver() {
    $c = config();
    return $c['db']['driver'];
}

function db() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $c = config();
    $d = $c['db'];
    try {
        if ($d['driver'] === 'mysql') {
            $pdo = new PDO(
                'mysql:host=' . $d['host'] . ';dbname=' . $d['name'] . ';charset=utf8mb4',
                $d['user'], $d['pass']
            );
        } else {
            $pdo = new PDO('sqlite:' . $d['sqlite_path']);
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        http_response_code(500);
        die('No se pudo conectar a la base de datos. Revisa includes/config.php');
    }
    return $pdo;
}

function q($sql, $params = array()) {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function fila($sql, $params = array()) {
    $r = q($sql, $params)->fetch();
    return $r ? $r : null;
}

function filas($sql, $params = array()) {
    return q($sql, $params)->fetchAll();
}

function crear_tablas() {
    $mysql = (db_driver() === 'mysql');
    $pk    = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $txt   = $mysql ? 'TEXT' : 'TEXT';
    $eng   = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';
    $vc    = $mysql ? 'VARCHAR(190)' : 'TEXT';

    $tablas = array(
        "CREATE TABLE IF NOT EXISTS usuarios (
            id $pk,
            usuario $vc NOT NULL UNIQUE,
            clave_hash $vc NOT NULL,
            nombre $vc NOT NULL,
            curso $vc NOT NULL DEFAULT '',
            rol $vc NOT NULL DEFAULT 'expositor',
            activo INT NOT NULL DEFAULT 1,
            creado $vc NOT NULL
        )$eng",
        "CREATE TABLE IF NOT EXISTS proyectos (
            id $pk,
            usuario_id INT NOT NULL,
            titulo $vc NOT NULL,
            curso $vc NOT NULL,
            expositores $txt NOT NULL,
            resena $txt NOT NULL,
            descripcion $txt NOT NULL,
            innovacion $txt NOT NULL,
            stand $vc NOT NULL DEFAULT '',
            video_url $vc NOT NULL DEFAULT '',
            imagen_principal $vc NOT NULL DEFAULT '',
            estado $vc NOT NULL DEFAULT 'pendiente',
            fecha $vc NOT NULL,
            creado $vc NOT NULL
        )$eng",
        "CREATE TABLE IF NOT EXISTS imagenes (
            id $pk,
            proyecto_id INT NOT NULL,
            archivo $vc NOT NULL,
            pie $vc NOT NULL DEFAULT ''
        )$eng",
        "CREATE TABLE IF NOT EXISTS valoraciones (
            id $pk,
            proyecto_id INT NOT NULL,
            nombre $vc NOT NULL DEFAULT '',
            estrellas INT NOT NULL,
            comentario $txt NOT NULL,
            huella $vc NOT NULL,
            fecha $vc NOT NULL,
            UNIQUE (proyecto_id, huella)
        )$eng",
        "CREATE TABLE IF NOT EXISTS ajustes (
            clave $vc NOT NULL PRIMARY KEY,
            valor $txt NOT NULL
        )$eng",
    );
    foreach ($tablas as $sql) {
        db()->exec($sql);
    }
}
