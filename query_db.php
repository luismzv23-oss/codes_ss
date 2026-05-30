<?php
// Boostrap CodeIgniter
define('FCPATH', __DIR__ . '/public/');
require __DIR__ . '/app/Config/Paths.php';
$paths = new Config\Paths();
require __DIR__ . '/system/bootstrap.php';

$db = \Config\Database::connect();

echo "--- SPORTS ---\n";
$sports = $db->table('sports')->get()->getResultArray();
foreach ($sports as $s) {
    echo "Sport ID: {$s['id']}, Name: {$s['name']}, Slug: {$s['slug']}, Active: {$s['active']}\n";
}

echo "\n--- LEAGUES ---\n";
$leagues = $db->table('leagues')->get()->getResultArray();
foreach ($leagues as $l) {
    echo "League ID: {$l['id']}, Sport ID: {$l['sport_id']}, Name: {$l['name']}, Country: {$l['country']}, Active: {$l['active']}\n";
}

echo "\n--- EVENTS (First 20) ---\n";
$events = $db->table('events')->orderBy('id', 'DESC')->limit(20)->get()->getResultArray();
foreach ($events as $e) {
    echo "Event ID: {$e['id']}, League ID: {$e['league_id']}, Home: {$e['home_team']}, Away: {$e['away_team']}, Start: {$e['start_time']}, Status: {$e['status']}\n";
}

echo "\n--- LIVE COUNT IN DB ---\n";
$liveCount = $db->table('events')->where('status', 'live')->countAllResults();
echo "Live count: {$liveCount}\n";
