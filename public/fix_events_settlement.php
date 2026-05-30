<?php
// Script temporal para corregir eventos finalizados sin marcador y ejecutar liquidación
// Eliminar este archivo después de usarlo.

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(dirname(__DIR__));

require_once '../vendor/autoload.php';

$app = \CodeIgniter\Config\Factories::config('App');

$dotenv = \CodeIgniter\Config\DotEnv::boot();

$db = \Config\Database::connect();

// 1. Asignar 0-0 a todos los eventos finished sin marcador
$db->query("UPDATE events SET score_home = 0, score_away = 0 WHERE status = 'finished' AND settled = 0 AND score_home IS NULL");
$fixed = $db->affectedRows();
echo "<p>✅ Eventos sin marcador corregidos (0-0 asignado): <strong>{$fixed}</strong></p>";

// 2. Ejecutar liquidación
$settlementService = new \App\Services\SettlementService();
$settlementService->settleEvents();
echo "<p>✅ Liquidación ejecutada correctamente.</p>";

// 3. Verificar estado
$events = $db->query("SELECT id, home_team, away_team, score_home, score_away, settled FROM events WHERE status = 'finished' ORDER BY id DESC LIMIT 20")->getResultArray();
echo "<table border='1' cellpadding='6'><tr><th>ID</th><th>Partido</th><th>Marcador</th><th>Liquidado</th></tr>";
foreach ($events as $e) {
    $score = ($e['score_home'] !== null) ? "{$e['score_home']} - {$e['score_away']}" : 'Sin marcador';
    echo "<tr><td>{$e['id']}</td><td>{$e['home_team']} vs {$e['away_team']}</td><td>{$score}</td><td>" . ($e['settled'] ? '✅' : '❌') . "</td></tr>";
}
echo "</table>";
