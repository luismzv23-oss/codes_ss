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

// Mock session and admin role
$session = \Config\Services::session();
$session->set([
    'isLoggedIn' => true,
    'role_id' => 1,
    'user_id' => 1
]);

// Mock post request values
$_POST['platform_name'] = 'Codex SS New';
$_POST['mp_qr_account'] = 'new.sportsbook.mp';
$_POST['mp_access_token'] = 'NEW-TEST-TOKEN';

$request = \Config\Services::request();
$request = $request->withMethod('post');

$dashboard = new \App\Controllers\Dashboard();
$dashboard->initController(
    $request,
    \Config\Services::response(),
    \Config\Services::logger()
);

$res = $dashboard->updateSettings();
$body = $res->getBody();
echo "Response from updateSettings: " . $body . "\n";

// Check database values
$settingModel = new \App\Models\SystemSettingModel();
echo "mp_qr_account in DB: " . $settingModel->getSetting('mp_qr_account') . "\n";
echo "mp_access_token in DB: " . $settingModel->getSetting('mp_access_token') . "\n";
echo "platform_name in DB: " . $settingModel->getSetting('platform_name') . "\n";
