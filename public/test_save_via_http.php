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

// Set up $_POST
$_POST['platform_name'] = 'Codex Sportsbook';
$_POST['base_url'] = 'http://localhost:8080';
$_POST['timezone'] = 'America/Argentina/Buenos_Aires';
$_POST['mp_qr_account'] = 'codex.sportsbook.mp';
$_POST['mp_access_token'] = 'APP_USR-12345678-TEST-TOKEN';

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

$settingModel = new \App\Models\SystemSettingModel();
$savedAccount = $settingModel->getSetting('mp_qr_account');
$savedToken = $settingModel->getSetting('mp_access_token');

header('Content-Type: application/json');
echo json_encode([
    'response_body' => json_decode($body, true),
    'saved_mp_qr_account' => $savedAccount,
    'saved_mp_access_token' => $savedToken
], JSON_PRETTY_PRINT);
