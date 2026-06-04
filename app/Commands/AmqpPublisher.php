<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class AmqpPublisher extends BaseCommand
{
    protected $group = 'B2B Integration';
    protected $name = 'amqp:publish';
    protected $description = 'Simulates a B2B provider (like Sportradar) sending a mock odds payload via RabbitMQ.';
    protected $usage = 'amqp:publish';

    public function run(array $params)
    {
        CLI::write('Conectando al simulador de proveedor...', 'green');

        try {
            // Conectar al RabbitMQ local
            $host = getenv('AMQP_HOST') ?: 'localhost';
            $port = getenv('AMQP_PORT') ?: 5672;
            $user = getenv('AMQP_USER') ?: 'guest';
            $pass = getenv('AMQP_PASS') ?: 'guest';

            $connection = new AMQPStreamConnection($host, $port, $user, $pass);
            $channel = $connection->channel();

            $exchange = 'unified_odds_feed';
            
            // Declarar el exchange por si no existe
            $channel->exchange_declare($exchange, 'topic', false, true, false);

            // Crear un JSON falso simulando un cambio de cuota
            $mockData = [
                'event_id' => 'sr:match:12345678',
                'type' => 'odds_change',
                'timestamp' => time(),
                'markets' => [
                    [
                        'id' => 1, // 1x2 market
                        'outcomes' => [
                            ['id' => '1', 'odds' => 2.10],
                            ['id' => 'X', 'odds' => 3.40],
                            ['id' => '2', 'odds' => 3.50],
                        ]
                    ]
                ]
            ];

            $jsonPayload = json_encode($mockData);
            $msg = new AMQPMessage($jsonPayload);
            
            // Publicar el mensaje en el exchange con un routing key inventado
            $routingKey = 'hi.pre.live.odds_change.soccer';
            $channel->basic_publish($msg, $exchange, $routingKey);

            CLI::write(" [x] ¡Mensaje falso de cuotas enviado exitosamente a RabbitMQ!", 'yellow');
            CLI::write(" Payload: " . $jsonPayload);

            $channel->close();
            $connection->close();

        } catch (\Exception $e) {
            CLI::error("Error publicando en AMQP: " . $e->getMessage());
            CLI::write("Asegúrate de que RabbitMQ esté corriendo en localhost:5672");
        }
    }
}
