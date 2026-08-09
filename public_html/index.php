<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determinar si la aplicación está en mantenimiento
if (file_exists($maintenance = __DIR__.'/../sigesto/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Registrar el autoloader de Composer
require __DIR__.'/../sigesto/vendor/autoload.php';

// Cargar la aplicación Laravel
(require __DIR__.'/../sigesto/bootstrap/app.php')
    ->handleRequest(Request::capture());