<?php
header('Content-Type: text/plain; charset=utf-8');
try {
    $pdo = new PDO("mysql:host=localhost;dbname=codex_ss", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT id, sport_key, league_name, home_team, away_team, start_time, status, created_at FROM staged_events WHERE status = 'pending_review'");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "PENDING STAGED EVENTS:\n";
    foreach ($rows as $row) {
        echo "ID: {$row['id']} | Sport: {$row['sport_key']} | League: {$row['league_name']} | Match: {$row['home_team']} vs {$row['away_team']} | StartTime: '{$row['start_time']}' | Status: {$row['status']} | CreatedAt: {$row['created_at']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
