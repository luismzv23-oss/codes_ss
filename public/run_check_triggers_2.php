<?php
header('Content-Type: text/plain; charset=utf-8');
try {
    $pdo = new PDO("mysql:host=localhost;dbname=codex_ss", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SHOW TRIGGERS");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Triggers count: " . count($triggers) . "\n";
    foreach ($triggers as $t) {
        echo "Trigger: {$t['Trigger']} | Event: {$t['Event']} | Table: {$t['Table']} | Timing: {$t['Timing']}\n";
        echo "Statement: \n{$t['Statement']}\n\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
