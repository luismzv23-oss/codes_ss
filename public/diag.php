<?php
define('ENVIRONMENT', 'development');
define('CodeIgniter\ENVIRONMENT', 'development');
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

$app = Config\Services::codeigniter();
$app->initialize();

header('Content-Type: text/plain; charset=utf-8');

$db = \Config\Database::connect();

echo "=== STAGED EVENTS COLUMNS ===\n";
$fields = $db->getFieldNames('staged_events');
print_r($fields);

echo "\n=== PENDING STAGED EVENTS ===\n";
$staged = $db->table('staged_events')
             ->where('status', 'pending_review')
             ->get()
             ->getResultArray();
foreach ($staged as $s) {
    echo "ID: {$s['id']} | {$s['home_team']} vs {$s['away_team']} | Start: {$s['start_time']} | Venue: " . ($s['venue'] ?? 'NULL') . " | Stage: " . ($s['stage'] ?? 'NULL') . " | Group: " . ($s['group_name'] ?? 'NULL') . "\n";
}

echo "\n=== EVENTS FLAGS IN DB ===\n";
$events = $db->table('events')
             ->select('id, home_team, home_flag, away_team, away_flag, start_time, venue, stage, group_name')
             ->orderBy('id', 'DESC')
             ->limit(10)
             ->get()
             ->getResultArray();
foreach ($events as $e) {
    echo "ID: {$e['id']} | {$e['home_team']} (" . ($e['home_flag'] ?? 'NULL') . ") vs {$e['away_team']} (" . ($e['away_flag'] ?? 'NULL') . ") | Start: {$e['start_time']} | Venue: " . ($e['venue'] ?? 'NULL') . "\n";
}
