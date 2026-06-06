<?php
header('Content-Type: text/plain; charset=utf-8');
$hosts = ['host.docker.internal', '172.17.0.1', '10.0.75.1'];
foreach ($hosts as $host) {
    $connection = @fsockopen($host, 3306, $errno, $errstr, 2);
    if (is_resource($connection)) {
        echo "Host {$host}:3306 is OPEN\n";
        fclose($connection);
    } else {
        echo "Host {$host}:3306 is CLOSED ({$errstr})\n";
    }
}
