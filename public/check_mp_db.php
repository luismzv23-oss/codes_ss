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
header('Content-Type: application/json');
echo json_encode([
    'mp_qr_account' => $settingModel->getSetting('mp_qr_account'),
    'mp_access_token' => $settingModel->getSetting('mp_access_token'),
], JSON_PRETTY_PRINT);
