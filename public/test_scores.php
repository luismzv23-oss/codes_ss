<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app = Config\Services::codeigniter();
$app->initialize();

$db = \Config\Database::connect();
$events = $db->table('events e')
            ->select('e.id, e.home_team, e.away_team, e.status, e.start_time, e.score_home, l.api_sport_key')
            ->join('leagues l', 'l.id = e.league_id', 'left')
            ->get()->getResultArray();

echo json_encode($events);
