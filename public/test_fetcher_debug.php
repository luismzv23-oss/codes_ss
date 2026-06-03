<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
$app = Config\Services::codeigniter();
if (file_exists(FCPATH . '../.env')) {
    $dotenv = new \CodeIgniter\Config\Dotenv(FCPATH . '../');
    $dotenv->load();
}
define('CodeIgniter\ENVIRONMENT', getenv('CI_ENVIRONMENT') ?: 'development');
$app->initialize();

header('Content-Type: text/plain; charset=utf-8');

// Check environment variables
$envSerp = env('SERPAPI_KEY');
$getenvSerp = getenv('SERPAPI_KEY');
echo "env('SERPAPI_KEY'): " . ($envSerp ? "FOUND (starts with " . substr($envSerp, 0, 5) . "...)" : "NOT FOUND") . "\n";
echo "getenv('SERPAPI_KEY'): " . ($getenvSerp ? "FOUND (starts with " . substr($getenvSerp, 0, 5) . "...)" : "NOT FOUND") . "\n";

// Query pending events
$db = \Config\Database::connect();
$events = $db->table('events e')
    ->select('e.id, e.home_team, e.away_team, e.status, e.start_time, l.name as league_name, l.api_sport_key')
    ->join('leagues l', 'l.id = e.league_id', 'left')
    ->where('e.score_home IS NULL', null, false)
    ->where('e.start_time <=', date('Y-m-d H:i:s'))
    ->orderBy('e.start_time', 'DESC')
    ->limit(5)
    ->get()
    ->getResultArray();

if (empty($events)) {
    echo "No pending matches (started in the past) found in database.\n";
    exit;
}

$fetcher = new \App\Services\ScoreFetcherService();

foreach ($events as $ev) {
    echo "\n----------------------------------------\n";
    echo "Testing Event: {$ev['home_team']} vs {$ev['away_team']} (ID: {$ev['id']})\n";
    echo "League: {$ev['league_name']} | ApiKey: {$ev['api_sport_key']}\n";
    
    // Call the method and capture detailed info
    $score = $fetcher->fetchScoreForEvent($ev, $ev['api_sport_key'] ?? '');
    echo "Final result from fetchScoreForEvent: " . ($score ? $score : "NULL (No disponible aún)") . "\n";
    
    // Run SerpApi search manually in this script to log details
    $apiKey = $envSerp ?: $getenvSerp;
    if ($apiKey) {
        $query = urlencode(trim($ev['home_team']) . ' vs ' . trim($ev['away_team']) . ' resultado');
        $url = "https://serpapi.com/search.json?engine=google&q={$query}&api_key={$apiKey}";
        echo "SerpApi URL: {$url}\n";
        
        try {
            $client = \Config\Services::curlrequest();
            $response = $client->request('GET', $url, [
                'http_errors' => false,
                'timeout' => 10
            ]);
            
            echo "Status Code: " . $response->getStatusCode() . "\n";
            if ($response->getStatusCode() === 200) {
                $body = $response->getBody();
                $data = json_decode($body, true);
                
                echo "Root keys in SerpApi response: " . implode(', ', array_keys($data)) . "\n";
                if (isset($data['sports_results'])) {
                    echo "sports_results keys: " . implode(', ', array_keys($data['sports_results'])) . "\n";
                    if (isset($data['sports_results']['game_spotlight'])) {
                        $spotlight = $data['sports_results']['game_spotlight'];
                        echo "game_spotlight: status=" . ($spotlight['status'] ?? 'N/A') . "\n";
                        if (isset($spotlight['teams'])) {
                            foreach ($spotlight['teams'] as $idx => $t) {
                                echo "  Team {$idx}: {$t['name']} - Score: " . ($t['score'] ?? 'N/A') . "\n";
                            }
                        }
                    }
                    if (isset($data['sports_results']['games'])) {
                        echo "Found games array in sports_results:\n";
                        print_r($data['sports_results']['games']);
                    }
                } else {
                    echo "No 'sports_results' key found in SerpApi response!\n";
                }
            } else {
                echo "Response body: " . substr($response->getBody(), 0, 500) . "\n";
            }
        } catch (\Exception $ex) {
            echo "Exception: " . $ex->getMessage() . "\n";
        }
    }
}
