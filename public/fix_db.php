<?php
header('Content-Type: text/plain; charset=utf-8');

$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected successfully to MySQL.\n";
    
    echo "Creating database c2701883_codexss_v2...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS c2701883_codexss_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database c2701883_codexss_v2 created successfully!\n";
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
