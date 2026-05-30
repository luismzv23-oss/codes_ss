<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
$bootstrap = __DIR__ . '/../app/Config/Boot.php';
require $bootstrap;

$db = \Config\Database::connect();

// Fetch leagueEvents for league 1
$events = $db->table('events')
             ->where('league_id', 1)
             ->orderBy('match_number', 'ASC')
             ->orderBy('start_time', 'ASC')
             ->get()
             ->getResultArray();

$finalEvents = array_filter($events, function($e) {
    return $e['match_number'] == '600' || $e['stage'] == 'Final';
});

echo "FINAL EVENTS UNDER LEAGUE 1:\n";
echo json_encode(array_values($finalEvents), JSON_PRETTY_PRINT) . "\n\n";

// Fetch leagueEvents for league 2
$events2 = $db->table('events')
             ->where('league_id', 2)
             ->orderBy('match_number', 'ASC')
             ->orderBy('start_time', 'ASC')
             ->get()
             ->getResultArray();

$finalEvents2 = array_filter($events2, function($e) {
    return $e['match_number'] == '600' || $e['stage'] == 'Final';
});

echo "FINAL EVENTS UNDER LEAGUE 2:\n";
echo json_encode(array_values($finalEvents2), JSON_PRETTY_PRINT) . "\n";
