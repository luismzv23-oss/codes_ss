<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();
if (! defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}
$app = Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();
$query = $db->query("EXPLAIN system_settings");
$schema = $query->getResultArray();

$rowsQuery = $db->query("SELECT * FROM system_settings");
$rows = $rowsQuery->getResultArray();

header('Content-Type: application/json');
echo json_encode([
    'schema' => $schema,
    'rows' => $rows
], JSON_PRETTY_PRINT);
