<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/') . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();

try {
    $db = \Config\Database::connect();
    $query = $db->query("SHOW TRIGGERS");
    $results = $query->getResultArray();
    echo "Triggers found: " . count($results) . "\n";
    print_r($results);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
