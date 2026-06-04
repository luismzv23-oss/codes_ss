<?php
set_time_limit(0);
$dir = dirname(__DIR__);
chdir($dir);

if (!file_exists('composer.phar')) {
    copy('https://getcomposer.org/download/latest-stable/composer.phar', 'composer.phar');
}

// Ejecutar composer update
$output = shell_exec('php composer.phar update 2>&1');

// Borrar el archivo fix después de ejecutar
@unlink(__FILE__);

echo "COMPOSER_UPDATE_DONE\n\n";
echo $output;
