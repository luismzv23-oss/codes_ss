<?php
// Valid environments: development, testing, production
define('ENVIRONMENT', 'development');

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Location of the Paths config file.
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();

// Location of the framework bootstrap file.
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Load environment settings from .env files into $_SERVER and $_ENV
require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();

// Run migrations
$forge = \Config\Database::forge();
$migrate = \Config\Services::migrations();

try {
    if ($migrate->latest()) {
        echo "Migrations executed successfully.\n";
    } else {
        echo "Nothing to migrate.\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage();
}
