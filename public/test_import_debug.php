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

$file = FCPATH . 'serpapi_today_amistosos.json';
$data = json_decode(file_get_contents($file), true);

foreach ($data['sports_results']['games'] as $match) {
    if (!isset($match['teams']) || count($match['teams']) < 2) continue;
    $home = $match['teams'][0]['name'] ?? '';
    $away = $match['teams'][1]['name'] ?? '';
    
    echo "Home: '$home' (len: " . strlen($home) . ")\n";
    echo "  - Hex bytes: " . bin2hex($home) . "\n";
    echo "  - Normalized: '" . testNormalize($home) . "'\n";
    echo "  - Flag: '" . ($loader->getFlagForTeam($home) ?? 'NULL') . "'\n";
    
    echo "Away: '$away' (len: " . strlen($away) . ")\n";
    echo "  - Hex bytes: " . bin2hex($away) . "\n";
    echo "  - Normalized: '" . testNormalize($away) . "'\n";
    echo "  - Flag: '" . ($loader->getFlagForTeam($away) ?? 'NULL') . "'\n";
    echo "\n";
}

function testNormalize(string $team): string
{
    $team = str_ireplace(
        ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'],
        ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'u', 'n'],
        $team
    );
    $team = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $team) ?: $team;
    $team = strtolower($team);
    $team = str_replace(["'", '`', '~', '^'], '', $team);
    $team = preg_replace('/\b(seleccion de futbol de|seleccion de|deportiva|fc|cf|sc|club|de|la|el|the)\b/i', ' ', $team) ?? $team;
    $team = preg_replace('/[^a-z0-9]+/', ' ', $team) ?? $team;
    return trim(preg_replace('/\s+/', ' ', $team) ?? $team);
}
