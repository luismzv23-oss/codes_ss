<?php
header('Content-Type: text/plain; charset=utf-8');

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'c2701883_codexss';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected.\n";
    
    try {
        echo "Creating test_table...\n";
        $pdo->exec("CREATE TABLE test_table (id INT)");
        echo "Successfully created test_table.\n";
        $pdo->exec("DROP TABLE test_table");
        echo "Successfully dropped test_table.\n";
    } catch (Exception $e) {
        echo "Error creating table: " . $e->getMessage() . "\n";
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
