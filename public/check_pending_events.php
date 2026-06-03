<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app = Config\Services::codeigniter();
if (file_exists(FCPATH . '../.env')) {
    $dotenv = new \CodeIgniter\Config\Dotenv(FCPATH . '../');
    $dotenv->load();
}
define('CodeIgniter\ENVIRONMENT', getenv('CI_ENVIRONMENT') ?: 'development');
$app->initialize();

$db = \Config\Database::connect();
$events = $db->table('events e')
    ->select('e.id, e.home_team, e.away_team, e.status, e.start_time, e.score_home, e.score_away, l.name as league_name, l.api_sport_key')
    ->join('leagues l', 'l.id = e.league_id', 'left')
    ->where('e.score_home IS NULL', null, false)
    ->orderBy('e.start_time', 'DESC')
    ->limit(50)
    ->get()
    ->getResultArray();

echo "Pending events in DB:\n";
foreach ($events as $ev) {
    echo "ID: {$ev['id']} | {$ev['league_name']} | {$ev['home_team']} vs {$ev['away_team']} | Status: {$ev['status']} | Start: {$ev['start_time']} | ApiKey: {$ev['api_sport_key']}\n";
}
