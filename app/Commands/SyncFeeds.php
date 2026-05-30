<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\SportsFeedService;

class SyncFeeds extends BaseCommand
{
    protected $group       = 'Sportsbook';
    protected $name        = 'sportsbook:sync';
    protected $description = 'Synchronizes live odds and events from external feed API.';

    public function run(array $params)
    {
        CLI::write('Starting sports feed synchronization...', 'yellow');

        $service = new SportsFeedService();
        
        try {
            $service->syncLiveFeed();
            CLI::write('✓ Feed synchronized successfully.', 'green');
        } catch (\Exception $e) {
            CLI::error('Sync failed: ' . $e->getMessage());
        }
    }
}
