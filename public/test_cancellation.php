<?php

header('Content-Type: text/plain; charset=utf-8');

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';
require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();

if (! defined('ENVIRONMENT')) {
    define('ENVIRONMENT', env('CI_ENVIRONMENT', 'production'));
}

$db = \Config\Database::connect();

try {
    $db->transStart();

    // 1. Crear un Deporte y Liga de prueba si no existen
    $sportId = $db->table('sports')->insert([
        'name' => 'Deporte de Prueba',
        'slug' => 'deporte-de-prueba',
        'active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    
    $leagueId = $db->table('leagues')->insert([
        'sport_id' => $sportId,
        'name' => 'Liga de Prueba',
        'country' => 'Argentina',
        'active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    // 2. Crear Eventos de prueba
    // Evento A: Se va a anular
    $eventIdA = $db->table('events')->insert([
        'league_id' => $leagueId,
        'home_team' => 'Equipo Local A',
        'away_team' => 'Equipo Visitante A',
        'start_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
        'status' => 'pending',
        'settled' => 0,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    // Evento B: No se anulará (se resolverá como ganado)
    $eventIdB = $db->table('events')->insert([
        'league_id' => $leagueId,
        'home_team' => 'Equipo Local B',
        'away_team' => 'Equipo Visitante B',
        'start_time' => date('Y-m-d H:i:s', strtotime('+3 hours')),
        'status' => 'pending',
        'settled' => 0,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    // 3. Crear Mercados y Odds
    // Evento A
    $marketIdA = $db->table('markets')->insert([
        'event_id' => $eventIdA,
        'name' => 'Ganador del Partido',
        'type' => '1x2',
        'status' => 'open',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    $oddIdA1 = $db->table('odds')->insert([
        'market_id' => $marketIdA,
        'selection' => '1',
        'odds_decimal' => 2.00,
        'active' => 1,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    // Evento B
    $marketIdB = $db->table('markets')->insert([
        'event_id' => $eventIdB,
        'name' => 'Ganador del Partido',
        'type' => '1x2',
        'status' => 'open',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    $oddIdB1 = $db->table('odds')->insert([
        'market_id' => $marketIdB,
        'selection' => '1',
        'odds_decimal' => 3.00,
        'active' => 1,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    // 4. Crear un Usuario de prueba y su Wallet
    $userId = $db->table('users')->insert([
        'username' => 'testuser_' . time(),
        'email' => 'testuser_' . time() . '@test.com',
        'password' => password_hash('password123', PASSWORD_BCRYPT),
        'role_id' => 2,
        'is_active' => 1,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    $walletId = $db->table('wallets')->insert([
        'user_id' => $userId,
        'balance' => 1000.00,
        'currency' => 'ARS',
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    // 5. Crear apuesta simple (en Evento A)
    $betSlipSimpleId = $db->table('bet_slips')->insert([
        'user_id' => $userId,
        'total_odds' => 2.00,
        'stake' => 100.00,
        'potential_payout' => 200.00,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    $db->table('bet_selections')->insert([
        'bet_slip_id' => $betSlipSimpleId,
        'odd_id' => $oddIdA1,
        'odd_at_bet_time' => 2.00,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    // 6. Crear apuesta combinada (en Evento A y Evento B)
    // Cuota total: 2.00 * 3.00 = 6.00
    $betSlipComboId = $db->table('bet_slips')->insert([
        'user_id' => $userId,
        'total_odds' => 6.00,
        'stake' => 100.00,
        'potential_payout' => 600.00,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    $db->table('bet_selections')->insert([
        'bet_slip_id' => $betSlipComboId,
        'odd_id' => $oddIdA1,
        'odd_at_bet_time' => 2.00,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    $db->table('bet_selections')->insert([
        'bet_slip_id' => $betSlipComboId,
        'odd_id' => $oddIdB1,
        'odd_at_bet_time' => 3.00,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    echo "=== ESTADO INICIAL ===\n";
    echo "Usuario Balance: 1000 K\n";
    echo "Apuesta Simple #{$betSlipSimpleId} (Pendiente, cuota 2.00, apuesta 100)\n";
    echo "Apuesta Combinada #{$betSlipComboId} (Pendiente, cuota 6.00, apuesta 100)\n\n";

    // 7. Ejecutar anulación del Evento A
    echo "=== EJECUTANDO ANULACIÓN DEL EVENTO A ===\n";
    $eventModel = new \App\Models\EventModel();
    $eventA = $eventModel->find($eventIdA);
    $eventModel->update($eventIdA, ['status' => 'cancelled', 'settled' => 0]);
    
    $settlementService = new \App\Services\SettlementService();
    $settled = $settlementService->settleCancelledEvent($eventModel->find($eventIdA));
    echo "Evento A liquidado: " . ($settled ? "SÍ" : "NO") . "\n\n";

    // 8. Verificar resultados en la BD
    $slipSimple = $db->table('bet_slips')->where('id', $betSlipSimpleId)->get()->getRowArray();
    $selSimple = $db->table('bet_selections')->where('bet_slip_id', $betSlipSimpleId)->get()->getRowArray();
    $wallet = $db->table('wallets')->where('id', $walletId)->get()->getRowArray();

    echo "=== COMPROBACIÓN APUESTA SIMPLE ===\n";
    echo "Estado Selección: {$selSimple['status']} (Esperado: lost)\n";
    echo "Estado Boleto: {$slipSimple['status']} (Esperado: lost)\n";
    echo "Cuota Final: {$slipSimple['total_odds']} (Esperado: original, ya que no se recalcula por pérdida)\n";
    echo "Pago: {$slipSimple['potential_payout']} (Esperado: original/perdido)\n";
    echo "Nuevo Balance: {$wallet['balance']} K (Esperado: 1000 K - sin reembolso)\n\n";

    // Verificar Apuesta Combinada
    $slipCombo = $db->table('bet_slips')->where('id', $betSlipComboId)->get()->getRowArray();
    $selsCombo = $db->table('bet_selections')->where('bet_slip_id', $betSlipComboId)->get()->getResultArray();

    echo "=== COMPROBACIÓN APUESTA COMBINADA ===\n";
    foreach ($selsCombo as $s) {
        $type = ($s['odd_id'] == $oddIdA1) ? "Evento A (Anulado)" : "Evento B (Activo)";
        echo "Selección {$type}: Estado={$s['status']} (Esperado: lost para Evento A, pending para Evento B)\n";
    }
    echo "Estado Boleto Combinado: {$slipCombo['status']} (Esperado: lost)\n\n";

    // 9. Resolver Evento B como ganado para verificar que el combo sigue siendo perdido
    echo "=== RESOLVIENDO EVENTO B COMO GANADO ===\n";
    $db->table('events')->where('id', $eventIdB)->update([
        'score_home' => 1,
        'score_away' => 0,
        'status' => 'finished',
        'settled' => 0,
    ]);
    
    // Liquidar evento B
    $settlementService->settleEvent($eventModel->find($eventIdB));

    $slipComboFinal = $db->table('bet_slips')->where('id', $betSlipComboId)->get()->getRowArray();
    $walletFinal = $db->table('wallets')->where('id', $walletId)->get()->getRowArray();

    echo "=== COMPROBACIÓN FINAL APUESTA COMBINADA ===\n";
    echo "Estado Boleto Combinado: {$slipComboFinal['status']} (Esperado: lost)\n";
    echo "Balance Final Usuario: {$walletFinal['balance']} K (Esperado: 1000 K - sin reembolso y combo perdido)\n\n";

    $db->transRollback(); // Revertir todo para no ensuciar la base de datos
    echo "Prueba completada con éxito. Transacción revertida para limpieza.\n";

} catch (Exception $e) {
    $db->transRollback();
    echo "ERROR DURANTE LA PRUEBA: " . $e->getMessage() . "\n";
}
