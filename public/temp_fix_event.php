<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
$bootstrap = __DIR__ . '/../app/Config/Boot.php';
require $bootstrap;

$db = \Config\Database::connect();

try {
    $db->transStart();

    // 1. Update the event
    $db->table('events')
       ->where('id', 1)
       ->update([
           'start_time' => '2026-05-30 13:00:00',
           'status' => 'pending',
           'score_home' => null,
           'score_away' => null,
           'settled' => 0
       ]);

    // 2. Update markets of this event to 'open'
    $db->table('markets')
       ->where('event_id', 1)
       ->update([
           'status' => 'open'
       ]);

    // 3. Get the market IDs
    $markets = $db->table('markets')
                  ->where('event_id', 1)
                  ->get()
                  ->getResultArray();

    $marketIds = array_column($markets, 'id');

    if (!empty($marketIds)) {
        // 4. Update the odds to active = 1 and status = 'pending'
        $db->table('odds')
           ->whereIn('market_id', $marketIds)
           ->update([
               'active' => 1,
               'status' => 'pending'
           ]);
    }

    $db->transComplete();

    if ($db->transStatus() === false) {
        echo json_encode(['status' => 'error', 'message' => 'Transaction failed']);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Event and its odds/markets successfully reset to Saturday May 30 13:00:00 pending']);
    }
} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
