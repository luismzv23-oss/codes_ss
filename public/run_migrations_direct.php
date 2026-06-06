<?php
header('Content-Type: text/plain; charset=utf-8');

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();

if (! defined('ENVIRONMENT')) {
    define('ENVIRONMENT', env('CI_ENVIRONMENT', 'production'));
}

$app = Config\Services::codeigniter();
$app->initialize();

echo "Bootstrapped CodeIgniter successfully.\n";

$migrate = \Config\Services::migrations();
try {
    echo "Running migrations...\n";
    if ($migrate->latest()) {
        echo "Exito. Migraciones ejecutadas al 100%.\n";
    } else {
        echo "No habia migraciones pendientes.\n";
    }
} catch (\Throwable $e) {
    echo "Error running migrations:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
