<?php
define('ENVIRONMENT', 'development');
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();

$db = \Config\Database::connect();

// Save API Key
$db->table('system_settings')->upsert([
    'setting_key' => 'odds_api_key',
    'setting_value' => '357002f026ea63c327e2af81e6d95dc4'
]);
echo "API Key guardada.\n";

// Get Leagues
$leagues = $db->table('leagues')->get()->getResultArray();
echo "Ligas actuales:\n";
foreach ($leagues as $l) {
    echo "ID: {$l['id']} | Name: {$l['name']} | API Key: {$l['api_sport_key']}\n";
}
