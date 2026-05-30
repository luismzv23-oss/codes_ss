<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\SportsFeedService;

class SyncFeedsDaemon extends BaseCommand
{
    protected $group       = 'Sportsbook';
    protected $name        = 'sportsbook:sync-daemon';
    protected $description = 'Runs the feed synchronization continuously every X seconds.';

    public function run(array $params)
    {
        $seconds = $params[0] ?? 30; // Por defecto 30 segundos para notar los cambios rápido
        CLI::write("Iniciando Demonio de Sincronización en Tiempo Real (Intervalo: {$seconds}s). Presiona CTRL+C para detener.", 'yellow');

        $service = new SportsFeedService();
        
        while (true) {
            try {
                $service->syncLiveFeed();
                CLI::write('[' . date('H:i:s') . '] ✓ Feed actualizado.', 'green');
            } catch (\Exception $e) {
                CLI::error('[' . date('H:i:s') . '] Error: ' . $e->getMessage());
            }

            sleep($seconds);
        }
    }
}
