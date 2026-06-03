<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
if (file_exists(FCPATH . '../.env')) {
    $dotenv = new \CodeIgniter\Config\Dotenv(FCPATH . '../');
    $dotenv->load();
}
define('CodeIgniter\ENVIRONMENT', getenv('CI_ENVIRONMENT') ?: 'development');
$app = Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();
$events = $db->table('events e')
    ->select('e.home_team, e.away_team, l.api_sport_key')
    ->join('leagues l', 'l.id = e.league_id', 'left')
    ->where('e.home_team', 'Marruecos')
    ->get()->getResultArray();

print_r($events);
