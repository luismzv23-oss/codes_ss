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

$controller = new \App\Controllers\Dashboard();
$ref = new ReflectionClass($controller);
$method = $ref->getMethod('flagMarkup');
$method->setAccessible(true);

echo "BD flag markup:\n";
echo $method->invoke($controller, 'bd') . "\n";
