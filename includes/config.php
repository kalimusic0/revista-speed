<?php
/*
 * CONFIGURACIÓN DE REVISTA SPEED
 *
 * Por defecto usa SQLite: la base de datos es UN archivo (data/revista.sqlite) que se crea sola.
 * No hay que crear ni conectar nada en el hosting.
 *
 * Si prefieres MySQL (el que ofrece tu hosting en cPanel):
 *   1) crea la base de datos y un usuario en cPanel -> "Bases de datos MySQL"
 *   2) cambia 'driver' a 'mysql' y llena host / name / user / pass
 *   3) abre install.php una vez
 */
return array(
    'db' => array(
        'driver'      => 'sqlite',                       // 'sqlite' o 'mysql'
        'sqlite_path' => __DIR__ . '/../data/revista.sqlite',
        'host'        => 'localhost',
        'name'        => '',
        'user'        => '',
        'pass'        => '',
    ),
    'sitio' => array(
        'nombre' => 'Revista Speed',
        'lema'   => 'Casa Abierta · Proyectos e ideas innovadoras',
        'evento' => 'Casa Abierta',          // texto que aparece en la portada
        'fecha_evento' => '',                // ej. '2026-12-05' para mostrar cuenta regresiva (opcional)
    ),
    'max_foto_mb' => 6,       // tamaño máximo por foto
    'max_lado_px' => 1600,    // las fotos se reducen a este tamaño máximo (más rápido para los visitantes)
);
