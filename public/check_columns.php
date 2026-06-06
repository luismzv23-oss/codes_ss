<?php
header('Content-Type: text/plain; charset=utf-8');

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'c2701883_codexss_v2';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Columns of staged_events:\n";
    $stmt = $pdo->query("DESCRIBE staged_events");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['Field']}: {$row['Type']} (Null: {$row['Null']})\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
