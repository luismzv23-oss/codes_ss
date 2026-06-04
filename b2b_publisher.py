import pika
import json
import time
import os

RABBITMQ_HOST = os.getenv('AMQP_HOST', 'localhost')
RABBITMQ_PORT = int(os.getenv('AMQP_PORT', 5672))

print("Conectando al simulador de proveedor...", flush=True)

try:
    connection = pika.BlockingConnection(
        pika.ConnectionParameters(host=RABBITMQ_HOST, port=RABBITMQ_PORT)
    )
    channel = connection.channel()

    exchange_name = 'unified_odds_feed'
    channel.exchange_declare(exchange=exchange_name, exchange_type='topic', durable=False)

    # Datos falsos de un partido
    mock_data = {
        'event_id': 'sr:match:12345678',
        'type': 'odds_change',
        'timestamp': int(time.time()),
        'markets': [
            {
                'id': 1,
                'outcomes': [
                    {'id': '1', 'odds': 2.15},
                    {'id': 'X', 'odds': 3.40},
                    {'id': '2', 'odds': 3.30},
                ]
            }
        ]
    }

    payload = json.dumps(mock_data)
    routing_key = 'hi.pre.live.odds_change.soccer'

    channel.basic_publish(
        exchange=exchange_name,
        routing_key=routing_key,
        body=payload
    )

    print(f" [x] ¡Mensaje falso enviado a RabbitMQ!\n Payload: {payload}", flush=True)
    connection.close()

except Exception as e:
    print(f"Error publicando en AMQP: {e}")
    print("¿Está RabbitMQ corriendo en localhost:5672?")
