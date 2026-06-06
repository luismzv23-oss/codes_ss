<?php
define('ENVIRONMENT', 'development');
define('Config\ENVIRONMENT', 'development');
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
$pathsPath = realpath(FCPATH . '../app/Config/Paths.php');
require $pathsPath;
$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app = require realpath($bootstrap) ?: $bootstrap;

$migrate = \Config\Services::migrations();
try {
    if ($migrate->latest()) {
        echo "Exito. Migraciones ejecutadas al 100%.";
    } else {
        echo "No habia migraciones pendientes o ya estaban ejecutadas.";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
