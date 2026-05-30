<?php
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Load paths config
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();

// Load bootstrap
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'bootstrap.php';

// Load env
require_once SYSTEMPATH . 'Config/DotEnv.php';
(new CodeIgniter\Config\DotEnv(ROOTPATH))->load();

if (! defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

$app = Config\Services::codeigniter();
$app->initialize();
$app->setContext('web');

header('Content-Type: text/plain; charset=utf-8');

$db = \Config\Database::connect();

try {
    echo "--- USER LUIS DETAILS ---\n";
    $user = $db->table('users')->where('username', 'luis')->get()->getRowArray();
    if (!$user) {
        echo "User 'luis' not found!\n";
        exit;
    }
    print_r($user);

    echo "\n--- WALLET DETAILS ---\n";
    $wallet = $db->table('wallets')->where('user_id', $user['id'])->get()->getRowArray();
    print_r($wallet);

    echo "\n--- ACTIVE ODDS FOR LIBERTAD VS UNIVERSIDAD CENTRAL ---\n";
    $odds = $db->table('odds o')
        ->join('markets m', 'm.id = o.market_id')
        ->join('events e', 'e.id = m.event_id')
        ->select('o.id, o.selection, o.odds_decimal, e.home_team, e.away_team, e.start_time, e.status as event_status')
        ->where('e.home_team', 'Libertad')
        ->get()
        ->getResultArray();
    print_r($odds);

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
