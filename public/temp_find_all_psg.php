<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
$bootstrap = __DIR__ . '/../app/Config/Boot.php';
require $bootstrap;

$db = \Config\Database::connect();
$events = $db->table('events')
             ->groupStart()
                 ->where('home_team LIKE', '%Paris%')
                 ->orWhere('away_team LIKE', '%Arsenal%')
                 ->orWhere('match_number', '600')
             ->groupEnd()
             ->get()
             ->getResultArray();

echo json_encode($events, JSON_PRETTY_PRINT);
