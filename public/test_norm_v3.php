<?php
header('Content-Type: text/plain; charset=utf-8');
define('ENVIRONMENT', 'development');
define('CodeIgniter\ENVIRONMENT', 'development');
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

$app = Config\Services::codeigniter();
$app->initialize();

$loader = new \App\Services\EventLoaderService();
echo "Irán: '" . $loader->getFlagForTeam("Irán") . "'\n";
echo "Malí: '" . $loader->getFlagForTeam("Malí") . "'\n";
echo "Afganistán: '" . $loader->getFlagForTeam("Afganistán") . "'\n";
echo "España: '" . $loader->getFlagForTeam("España") . "'\n";
echo "Japón: '" . $loader->getFlagForTeam("Japón") . "'\n";
