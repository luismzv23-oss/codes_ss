<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
$bootstrap = __DIR__ . '/../app/Config/Boot.php';
require $bootstrap;

$db = \Config\Database::connect();

$leagues = $db->table('leagues')->get()->getResultArray();
echo "LEAGUES:\n";
echo json_encode($leagues, JSON_PRETTY_PRINT) . "\n\n";

echo "EVENTS SUMMARY BY LEAGUE:\n";
$eventsSummary = $db->table('events')
                    ->select('league_id, status, COUNT(*) as count')
                    ->groupBy('league_id, status')
                    ->get()
                    ->getResultArray();
echo json_encode($eventsSummary, JSON_PRETTY_PRINT) . "\n";
