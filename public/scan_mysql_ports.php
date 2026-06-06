<?php
header('Content-Type: text/plain; charset=utf-8');
for ($port = 3300; $port <= 3320; $port++) {
    $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.5);
    if (is_resource($connection)) {
        echo "Port {$port} is OPEN\n";
        fclose($connection);
    }
}
echo "Scan complete.\n";
