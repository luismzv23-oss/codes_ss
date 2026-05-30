<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=codex_ss", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $now = date('Y-m-d H:i:s');
    $twoHoursAgo = date('Y-m-d H:i:s', strtotime('-2 hours'));
    echo "<p>NOW: $now</p>";
    echo "<p>TWO HOURS AGO: $twoHoursAgo</p>";

    // 1. Transition pending -> live
    $stmt1 = $pdo->prepare("UPDATE events SET status = 'live' WHERE status = 'pending' AND start_time <= :now AND start_time > :twoHoursAgo");
    $stmt1->execute(['now' => $now, 'twoHoursAgo' => $twoHoursAgo]);
    echo "<p>Pending -> Live updated: " . $stmt1->rowCount() . " rows</p>";

    // 2. Transition pending/live -> finished
    $stmt2 = $pdo->prepare("UPDATE events SET status = 'finished', settled = 0 WHERE status IN ('pending', 'live') AND start_time <= :twoHoursAgo");
    $stmt2->execute(['twoHoursAgo' => $twoHoursAgo]);
    echo "<p>Pending/Live -> Finished updated: " . $stmt2->rowCount() . " rows</p>";

    // Check Copa Libertadores matches
    $stmt = $pdo->query("SELECT id, home_team, away_team, start_time, status FROM events WHERE league_id = 5");
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>ID: {$row['id']} | {$row['home_team']} vs {$row['away_team']} | Start: {$row['start_time']} | Status: {$row['status']}</li>";
    }
    echo "</ul>";

} catch (Exception $e) {
    echo "<p>ERROR: " . $e->getMessage() . "</p>";
}
?>
