<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Redis Queue Worker placeholder.
 * 
 * La ingesta real se hace con b2b_consumer.py (Python + pika + redis).
 * Este comando queda como stub para cuando se instalen las
 * dependencias PHP (composer require predis/predis).
 */
class OddsWorker extends BaseCommand
{
    protected $group       = 'B2B Integration';
    protected $name        = 'queue:work';
    protected $description = 'Processes B2B payloads from Redis queue and updates MySQL/WebSockets.';
    protected $usage       = 'queue:work';

    public function run(array $params)
    {
        CLI::write('Odds Worker (PHP) — stub', 'green');
        CLI::write('La dependencia predis aún no está instalada.', 'yellow');
        CLI::write('Usa el consumidor Python en su lugar:  python b2b_consumer.py', 'yellow');
    }
}
