<?php
header('Content-Type: text/plain; charset=utf-8');
echo "Checking composer status...\n";
$vendorPath = realpath(__DIR__ . '/../vendor');
if ($vendorPath && is_dir($vendorPath)) {
    echo "vendor/ exists: YES\n";
    echo "vendor/composer/autoload_real.php exists: " . (file_exists($vendorPath . '/composer/autoload_real.php') ? 'YES' : 'NO') . "\n";
    echo "vendor/sabberworm/php-css-parser/ exists: " . (is_dir($vendorPath . '/sabberworm/php-css-parser') ? 'YES' : 'NO') . "\n";
    echo "vendor/thecodingmachine/safe/ exists: " . (is_dir($vendorPath . '/thecodingmachine/safe') ? 'YES' : 'NO') . "\n";
} else {
    echo "vendor/ exists: NO\n";
}
