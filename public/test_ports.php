<?php
header('Content-Type: text/plain; charset=utf-8');
$ports = [3306, 3307, 3308, 80, 8080, 443, 6379];
foreach ($ports as $port) {
    $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 2);
    if (is_resource($connection)) {
        echo "Port {$port} is OPEN\n";
        fclose($connection);
    } else {
        echo "Port {$port} is CLOSED ({$errstr})\n";
    }
}
