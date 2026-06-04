<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * AMQP Consumer placeholder.
 * 
 * La ingesta real se hace con b2b_consumer.py (Python + pika).
 * Este comando queda como stub para cuando se instalen las
 * dependencias PHP (composer require php-amqplib/php-amqplib predis/predis).
 */
class AmqpConsumer extends BaseCommand
{
    protected $group       = 'B2B Integration';
    protected $name        = 'amqp:consume';
    protected $description = 'Listens to a B2B AMQP feed (e.g., Sportradar UOF) and queues payloads into Redis.';
    protected $usage       = 'amqp:consume';

    public function run(array $params)
    {
        CLI::write('AMQP Consumer (PHP) — stub', 'green');
        CLI::write('Las dependencias php-amqplib y predis aún no están instaladas.', 'yellow');
        CLI::write('Usa el consumidor Python en su lugar:  python b2b_consumer.py', 'yellow');
    }
}
