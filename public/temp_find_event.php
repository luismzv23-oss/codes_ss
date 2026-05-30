<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
$bootstrap = __DIR__ . '/../app/Config/Boot.php';
require $bootstrap;

$db = \Config\Database::connect();
$event = $db->table('events')
            ->where('home_team LIKE', '%Paris%')
            ->orWhere('away_team LIKE', '%Arsenal%')
            ->get()
            ->getResultArray();

echo json_encode($event, JSON_PRETTY_PRINT);
