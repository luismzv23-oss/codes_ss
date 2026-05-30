<?php
require '../vendor/autoload.php';
$db = \Config\Database::connect();
$leagues = $db->table('leagues')->get()->getResultArray();
echo "LEAGUES:\n";
print_r($leagues);

$events = $db->table('events')->get()->getResultArray();
echo "\nEVENTS:\n";
print_r($events);
