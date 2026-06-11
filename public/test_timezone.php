<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');

$utcString = "2026-06-09 15:38:34 UTC";
$timestamp = strtotime($utcString);
$localString = date("Y-m-d H:i:s", $timestamp);

echo "UTC Input: " . $utcString . "\n";
echo "Timestamp: " . $timestamp . "\n";
echo "Local Output (Buenos Aires): " . $localString . "\n";

// Test using DateTime class
$dt = new DateTime($utcString);
$dt->setTimezone(new DateTimeZone('America/Argentina/Buenos_Aires'));
echo "DateTime Local: " . $dt->format("Y-m-d H:i:s") . "\n";
