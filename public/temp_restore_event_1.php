<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
$bootstrap = __DIR__ . '/../app/Config/Boot.php';
require $bootstrap;

$db = \Config\Database::connect();

try {
    $db->transStart();

    // 1. Restore Event 1 (Athletic Club vs Arsenal)
    $db->table('events')
       ->where('id', 1)
       ->update([
           'start_time' => '2025-09-16 21:00:00',
           'status' => 'finished',
           'score_home' => 0,
           'score_away' => 2,
           'settled' => 1
       ]);

    // Restore Event 1 markets to closed
    $db->table('markets')
       ->where('event_id', 1)
       ->update(['status' => 'closed']);

    // Get Event 1 market IDs
    $markets1 = $db->table('markets')->where('event_id', 1)->get()->getResultArray();
    $marketIds1 = array_column($markets1, 'id');
    if (!empty($marketIds1)) {
        // Set odds for Event 1: Arsenal won, Athletic Club lost, Draw lost
        // selection: 'Arsenal' -> status = 'won'
        // selection: 'Athletic Club' -> status = 'lost'
        // selection: 'Empate' -> status = 'lost'
        $db->table('odds')
           ->whereIn('market_id', $marketIds1)
           ->update(['active' => 0]);

        $db->table('odds')
           ->whereIn('market_id', $marketIds1)
           ->where('selection', 'Arsenal')
           ->update(['status' => 'won']);

        $db->table('odds')
           ->whereIn('market_id', $marketIds1)
           ->where('selection', 'Athletic Club')
           ->update(['status' => 'lost']);

        $db->table('odds')
           ->whereIn('market_id', $marketIds1)
           ->where('selection', 'Empate')
           ->update(['status' => 'lost']);
    }


    // 2. Fix Event 145 (Paris Saint-Germain vs Arsenal - Actual Final)
    $db->table('events')
       ->where('id', 145)
       ->update([
           'start_time' => '2026-05-30 13:00:00',
           'status' => 'pending',
           'score_home' => null,
           'score_away' => null,
           'settled' => 0
       ]);

    // Reset Event 145 markets to open
    $db->table('markets')
       ->where('event_id', 145)
       ->update(['status' => 'open']);

    // Get Event 145 market IDs
    $markets145 = $db->table('markets')->where('event_id', 145)->get()->getResultArray();
    $marketIds145 = array_column($markets145, 'id');
    if (!empty($marketIds145)) {
        // Reset all odds to active = 1 and status = pending
        $db->table('odds')
           ->whereIn('market_id', $marketIds145)
           ->update([
               'active' => 1,
               'status' => 'pending'
           ]);
    }

    $db->transComplete();

    if ($db->transStatus() === false) {
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed']);
    } else {
        echo json_encode([
            'status' => 'success',
            'message' => 'Successfully restored Event 1 and corrected Event 145 (PSG vs Arsenal Final)'
        ]);
    }

} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
