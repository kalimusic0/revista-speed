<?php
require_once __DIR__ . '/includes/funciones.php';
$_SESSION = array();
session_destroy();
redirigir('index.php');
