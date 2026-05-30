<?php
define('ENVIRONMENT', 'development');
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();

$db = \Config\Database::connect();

// 1. Resetear eventos que quedaron con 0-0 falso (settled=1) para que se re-liquiden con el marcador real
$affected = $db->table('events')
    ->where('status', 'finished')
    ->where('score_home', 0)
    ->where('score_away', 0)
    ->update([
        'score_home' => null,
        'score_away' => null,
        'settled' => 0
    ]);

echo "Paso 1: Scores 0-0 reseteados a NULL para reintento.\n";

// 2. Ahora ejecutar el fetcher para traer los marcadores reales
$fetcher = new \App\Services\ScoreFetcherService();

$eventsWithoutScore = $db->table('events e')
    ->select('e.id, e.home_team, e.away_team, l.api_sport_key')
    ->join('leagues l', 'l.id = e.league_id', 'left')
    ->where('e.status', 'finished')
    ->where('e.score_home IS NULL', null, false)
    ->get()->getResultArray();

echo "Paso 2: " . count($eventsWithoutScore) . " eventos sin marcador encontrados.\n\n";

foreach ($eventsWithoutScore as $ev) {
    echo "Evento #{$ev['id']}: {$ev['home_team']} vs {$ev['away_team']} (sport_key: {$ev['api_sport_key']})\n";
    
    $score = null;
    if (!empty($ev['api_sport_key'])) {
        $score = $fetcher->fetchScoreForEvent($ev, $ev['api_sport_key']);
    }

    if ($score) {
        [$home, $away] = explode('-', $score);
        $db->table('events')->where('id', $ev['id'])->update([
            'score_home' => (int)$home,
            'score_away' => (int)$away
        ]);
        echo "  ✓ Marcador REAL asignado: {$home}-{$away}\n\n";
    } else {
        echo "  ✗ Marcador no disponible en la API (se reintentará automáticamente)\n\n";
    }
}

// 3. Ejecutar liquidación
echo "Paso 3: Ejecutando liquidación...\n";
$settlementService = new \App\Services\SettlementService();
$settlementService->settleEvents();
echo "Liquidación completada.\n";

// 4. Mostrar estado final
$events = $db->table('events')->where('status', 'finished')->get()->getResultArray();
echo "\n=== ESTADO FINAL ===\n";
foreach ($events as $e) {
    $score = ($e['score_home'] !== null) ? "{$e['score_home']}-{$e['score_away']}" : "SIN MARCADOR";
    $settled = $e['settled'] ? "LIQUIDADO" : "PENDIENTE";
    echo "#{$e['id']}: {$e['home_team']} vs {$e['away_team']} → {$score} ({$settled})\n";
}
