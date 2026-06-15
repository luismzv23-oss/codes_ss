<?php
header('Content-Type: text/plain; charset=utf-8');
define('ENVIRONMENT', 'development');
define('CodeIgniter\ENVIRONMENT', 'development');
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

$app = Config\Services::codeigniter();
$app->initialize();

$loader = new \App\Services\EventLoaderService();
$e = [
    'sport_key' => 'serpapi_football',
    'home_team' => 'Irán',
    'away_team' => 'Malí',
    'start_time' => '2026-06-15 00:00:00'
];

echo "Calling getFlagForTeam directly:\n";
echo "  Home flag: '" . ($loader->getFlagForTeam($e['home_team']) ?? 'NULL') . "'\n";
echo "  Away flag: '" . ($loader->getFlagForTeam($e['away_team']) ?? 'NULL') . "'\n";

echo "\nCalling enrichStagedEventMetadata:\n";
$metadata = $loader->enrichStagedEventMetadata($e);
echo "Result: " . json_encode($metadata) . "\n";
