<?php
define('ENVIRONMENT', 'development');
define('CodeIgniter\ENVIRONMENT', 'development');

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Load our paths config file
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();

// Location of the framework bootstrap file.
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Load environment settings from .env files into $_SERVER and $_ENV
require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();

$app = Config\Services::codeigniter();
$app->initialize();

header('Content-Type: text/plain; charset=utf-8');

$db = \Config\Database::connect();
// Clear any pending review staged events first
$db->table('staged_events')->where('status', 'pending_review')->delete();

$file = FCPATH . 'serpapi_today_amistosos.json';
if (!file_exists($file)) {
    die("File serpapi_today_amistosos.json not found!\n");
}
$data = json_decode(file_get_contents($file), true);

$loader = new \App\Services\EventLoaderService();

// Let's reflect on fetchAndStageSerpApi or replicate its insertion logic
$batchId = 'test-batch-serpapi-123';
$stagedEventModel = new \App\Models\StagedEventModel();

$query = 'Amistosos Internacionales Hoy';
$leagueName = 'Amistosos internacionales';
$country = 'Internacional';

$ref = new ReflectionClass($loader);
$parseMethod = $ref->getMethod('parseSerpApiStartTime');
$parseMethod->setAccessible(true);

$extractVenueMethod = $ref->getMethod('extractVenueName');
$extractVenueMethod->setAccessible(true);

$extractGroupMethod = $ref->getMethod('extractGroupName');
$extractGroupMethod->setAccessible(true);

$buildVenueUrlMethod = $ref->getMethod('buildVenueSearchUrl');
$buildVenueUrlMethod->setAccessible(true);

foreach ($data['sports_results']['games'] as $match) {
    if (!isset($match['teams']) || count($match['teams']) < 2) {
        continue;
    }

    $home = $match['teams'][0]['name'] ?? '';
    $away = $match['teams'][1]['name'] ?? '';
    $stage = mb_substr($match['stage'] ?? $match['round'] ?? $match['phase'] ?? '', 0, 80);
    $groupName = mb_substr($extractGroupMethod->invoke($loader, $match, $leagueName), 0, 50);
    $venue = mb_substr($extractVenueMethod->invoke($loader, $match), 0, 150);
    $venueUrl = $buildVenueUrlMethod->invoke($loader, $venue, $home, $away);
    $startTime = $parseMethod->invoke($loader, $match, $query);

    $markets = [[
        'name' => 'Ganador del Partido',
        'type' => '1x2',
        'odds' => [
            ['selection' => '1', 'odds' => 2.10],
            ['selection' => 'X', 'odds' => 3.20],
            ['selection' => '2', 'odds' => 2.90],
        ]
    ]];

    $oddsJson = json_encode($markets);

    $stagedEventModel->insert([
        'batch_id'       => $batchId,
        'sport_key'      => 'serpapi_football',
        'league_name'    => $leagueName,
        'league_country' => $country,
        'home_team'      => $home,
        'away_team'      => $away,
        'start_time'     => $startTime,
        'stage'          => $stage ?: null,
        'group_name'     => $groupName ?: null,
        'venue'          => $venue ?: null,
        'venue_url'      => $venueUrl,
        'odds_data'      => $oddsJson,
        'status'         => 'pending_review',
    ]);
}

echo "Inserted games successfully.\n\n";

// Now run the logic of Dashboard::stagedEvents() to see what it outputs for these records
$staged = $db->table('staged_events')
    ->where('status', 'pending_review')
    ->orderBy('id', 'DESC')
    ->get()
    ->getResultArray();

echo "Running Dashboard formatting logic:\n";
foreach ($staged as &$e) {
    $needsMetadata = empty($e['venue']) || empty($e['start_time']) || substr((string) ($e['start_time'] ?? ''), 11) === '00:00:00';
    echo "Match: {$e['home_team']} vs {$e['away_team']}\n";
    echo "  - Before enrichment start_time: " . ($e['start_time'] ?? 'NULL') . "\n";
    echo "  - Needs Metadata: " . ($needsMetadata ? 'YES' : 'NO') . "\n";
    
    if ($needsMetadata) {
        $metadata = $loader->enrichStagedEventMetadata($e);
        echo "  - Metadata Returned: " . json_encode($metadata) . "\n";
        $updates = [];
        foreach (['start_time', 'stage', 'group_name', 'venue', 'venue_url'] as $field) {
            if (!empty($metadata[$field]) && empty($e[$field])) {
                $updates[$field] = $metadata[$field];
                $e[$field] = $metadata[$field];
            }
        }
        if (!empty($metadata['start_time']) && substr((string) ($e['start_time'] ?? ''), 11) === '00:00:00') {
            $updates['start_time'] = $metadata['start_time'];
            $e['start_time'] = $metadata['start_time'];
        }
        if ($updates !== []) {
            echo "  - Updating DB fields: " . json_encode($updates) . "\n";
            $stagedEventModel->update((int) $e['id'], $updates);
        }
    }
    
    $timestamp = strtotime((string) ($e['start_time'] ?? ''));
    $hasRealTime = substr((string) ($e['start_time'] ?? ''), 11) !== '00:00:00';
    $matchDateLabel = $timestamp !== false
        ? ($hasRealTime ? date('d/m/Y H:i', $timestamp) : date('d/m/Y', $timestamp) . ' (A confirmar)')
        : 'Fecha no disponible';
    
    echo "  - Final start_time: " . ($e['start_time'] ?? 'NULL') . "\n";
    echo "  - Match Date Label: {$matchDateLabel}\n\n";
}
