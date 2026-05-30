<?php
header('Content-Type: text/plain; charset=utf-8');
echo "PHP date('Y-m-d H:i:s'): " . date('Y-m-d H:i:s') . "\n";
echo "PHP date_default_timezone_get(): " . date_default_timezone_get() . "\n";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=codex_ss", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- SPORTS ---\n";
    $stmt = $pdo->query("SELECT * FROM sports");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Active: {$row['active']}\n";
    }

    echo "\n--- LEAGUES ---\n";
    $stmt = $pdo->query("SELECT * FROM leagues");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Sport ID: {$row['sport_id']} | Active: {$row['active']}\n";
    }

    echo "--- RUNNING STATUS UPDATE ---\n";
    $now = date('Y-m-d H:i:s');
    $twoHoursAgo = date('Y-m-d H:i:s', strtotime('-2 hours'));
    echo "now: $now\ntwoHoursAgo: $twoHoursAgo\n";

    // 1. Transition pending -> live
    $stmt1 = $pdo->prepare("UPDATE events SET status = 'live' WHERE status = 'pending' AND start_time <= :now AND start_time > :twoHoursAgo");
    $stmt1->execute(['now' => $now, 'twoHoursAgo' => $twoHoursAgo]);
    echo "Pending -> Live updated rows: " . $stmt1->rowCount() . "\n";

    // 2. Transition pending/live -> finished
    $stmt2 = $pdo->prepare("UPDATE events SET status = 'finished', settled = 0 WHERE status IN ('pending', 'live') AND start_time <= :twoHoursAgo");
    $stmt2->execute(['twoHoursAgo' => $twoHoursAgo]);
    echo "Pending/Live -> Finished updated rows: " . $stmt2->rowCount() . "\n";

    echo "\n--- LIBERTADORES EVENTS ON 2026-05-21 ---\n";
    $stmt = $pdo->query("SELECT e.id, e.home_team, e.away_team, e.start_time, e.status, e.league_id, l.name as league_name, l.active as league_active, s.name as sport_name, s.active as sport_active 
                         FROM events e 
                         LEFT JOIN leagues l ON l.id = e.league_id 
                         LEFT JOIN sports s ON s.id = l.sport_id 
                         WHERE e.start_time LIKE '2026-05-21%'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "Event ID: {$row['id']} | {$row['home_team']} vs {$row['away_team']} | Start: {$row['start_time']} | Status: {$row['status']} | League ID: {$row['league_id']} | League: {$row['league_name']} (Active: {$row['league_active']}) | Sport: {$row['sport_name']} (Active: {$row['sport_active']})\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
