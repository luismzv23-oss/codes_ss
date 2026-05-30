<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
$bootstrap = __DIR__ . '/../app/Config/Boot.php';
require $bootstrap;

$db = \Config\Database::connect();
$events = $db->table('events')
             ->where('home_team', 'Paris Saint-Germain')
             ->where('away_team', 'Arsenal')
             ->get()
             ->getResultArray();

echo json_encode($events, JSON_PRETTY_PRINT);
