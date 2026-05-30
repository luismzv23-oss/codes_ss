<?php
header('Content-Type: text/plain; charset=utf-8');
try {
    $pdo = new PDO("mysql:host=localhost;dbname=codex_ss", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "--- DEPORTES EN LA BD ---\n";
    $stmt = $pdo->query("SELECT id, name, active FROM sports");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Active: {$row['active']}\n";
    }

    echo "\n--- LIGAS EN LA BD ---\n";
    $stmt = $pdo->query("SELECT id, name, sport_id, active FROM leagues");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | Name: {$row['name']} | Sport ID: {$row['sport_id']} | Active: {$row['active']}\n";
    }

    echo "\n--- DETALLE PARTIDOS LIBERTADORES 21/05/2026 ---\n";
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
