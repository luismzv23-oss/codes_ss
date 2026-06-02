<?php
require 'vendor/autoload.php';
require 'system/bootstrap.php';

$cache = \Config\Services::cache();
$cache->clean();
echo "Cache cleared!";
