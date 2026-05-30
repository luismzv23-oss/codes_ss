<?php
header('Content-Type: text/plain; charset=utf-8');
try {
    $pdo = new PDO("mysql:host=localhost;dbname=codex_ss", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT * FROM events WHERE id = 1517");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "Found Event: ID: {$row['id']} | {$row['home_team']} vs {$row['away_team']} | Start: {$row['start_time']} | Status: {$row['status']}\n";
    } else {
        echo "Event ID 1517 not found!\n";
    }

    $stmt = $pdo->query("SELECT COUNT(*) FROM events");
    echo "Total events: " . $stmt->fetchColumn() . "\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
