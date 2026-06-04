import pika
import redis
import json
import time
import os

# Configuración (puedes usar variables de entorno o .env con python-dotenv)
RABBITMQ_HOST = os.getenv('AMQP_HOST', 'localhost')
RABBITMQ_PORT = int(os.getenv('AMQP_PORT', 5672))
REDIS_HOST = os.getenv('REDIS_HOST', '127.0.0.1')
REDIS_PORT = int(os.getenv('REDIS_PORT', 6379))

print("Starting Python AMQP Consumer...", flush=True)

try:
    # Conectar a Redis
    redis_client = redis.Redis(host=REDIS_HOST, port=REDIS_PORT, decode_responses=True)
    
    # Conectar a RabbitMQ
    connection = pika.BlockingConnection(
        pika.ConnectionParameters(host=RABBITMQ_HOST, port=RABBITMQ_PORT)
    )
    channel = connection.channel()

    exchange_name = 'unified_odds_feed'
    queue_name = 'codex_ss_ingestion_queue'

    channel.exchange_declare(exchange=exchange_name, exchange_type='topic', durable=False)
    channel.queue_declare(queue=queue_name, durable=True)
    channel.queue_bind(exchange=exchange_name, queue=queue_name, routing_key='#')

    def callback(ch, method, properties, body):
        routing_key = method.routing_key
        # Enviar instantáneamente a Redis (como string)
        redis_client.lpush('b2b_raw_payloads', body)
        
        print(f" [x] Recibido y encolado en Redis. Routing Key: {routing_key}", flush=True)
        # Acusar recibo a RabbitMQ para que sepa que ya lo guardamos
        ch.basic_ack(delivery_tag=method.delivery_tag)

    # Procesar 1 mensaje a la vez antes de pedir el siguiente
    channel.basic_qos(prefetch_count=1)
    channel.basic_consume(queue=queue_name, on_message_callback=callback)

    print(' [*] Esperando mensajes B2B. Presiona CTRL+C para salir.', flush=True)
    channel.start_consuming()

except Exception as e:
    print(f"Error de conexión: {e}")
