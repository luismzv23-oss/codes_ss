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

header('Content-Type: text/plain; charset=utf-8');

$db = \Config\Database::connect();
$row = $db->table('events e')
    ->select('e.*, l.name as league_name, l.active as league_active, s.name as sport_name, s.active as sport_active')
    ->join('leagues l', 'l.id = e.league_id', 'left')
    ->join('sports s', 's.id = l.sport_id', 'left')
    ->groupStart()
        ->like('e.home_team', 'Gibraltar')
        ->orLike('e.away_team', 'Gibraltar')
    ->groupEnd()
    ->get()
    ->getRowArray();

if (!$row) {
    echo "Event involving 'Gibraltar' not found in database.\n";
    exit;
}

echo "Event ID: {$row['id']}\n";
echo "Teams: {$row['home_team']} vs {$row['away_team']}\n";
echo "Status: {$row['status']}\n";
echo "Start Time: {$row['start_time']}\n";
echo "Score: {$row['score_home']} - {$row['score_away']}\n";
echo "League ID: {$row['league_id']}\n";
echo "League Name: {$row['league_name']}\n";
echo "League Active: {$row['league_active']}\n";
echo "Sport Name: {$row['sport_name']}\n";
echo "Sport Active: {$row['sport_active']}\n";

// Count markets
$markets = $db->table('markets')->where('event_id', $row['id'])->countAllResults();
echo "Markets count: {$markets}\n";

if ($markets > 0) {
    $odds = $db->table('odds o')
        ->join('markets m', 'm.id = o.market_id')
        ->where('m.event_id', $row['id'])
        ->countAllResults();
    echo "Odds selection count: {$odds}\n";
}
