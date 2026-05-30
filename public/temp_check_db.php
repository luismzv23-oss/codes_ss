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

$settingModel = new \App\Models\SystemSettingModel();
$all = $settingModel->findAll();
echo "=== DUMPING SYSTEM SETTINGS ===\n";
foreach ($all as $row) {
    echo $row['key'] . " => " . $row['value'] . "\n";
}
echo "=== END OF DUMP ===\n";
