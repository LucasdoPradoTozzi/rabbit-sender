<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;
use Illuminate\Support\Facades\Log;

class RabbitMQService
{
    protected $connection = null;
    protected $channel = null;

    /**
     * Estabelece conexão com o RabbitMQ (com suporte a SSL)
     */
    protected function connect(): void
    {
        if ($this->connection === null || !$this->connection->isConnected()) {
            $host = config('rabbitmq.host');
            $port = config('rabbitmq.port');
            $user = config('rabbitmq.user');
            $password = config('rabbitmq.password');
            $vhost = config('rabbitmq.vhost');

            // Usa SSL se a porta for 5671
            if ($port == 5671) {
                $this->connection = new AMQPSSLConnection(
                    $host,
                    $port,
                    $user,
                    $password,
                    $vhost,
                    [
                        'verify_peer' => true,
                        'verify_peer_name' => true,
                        'allow_self_signed' => false
                    ]
                );
            } else {
                $this->connection = new AMQPStreamConnection(
                    $host,
                    $port,
                    $user,
                    $password,
                    $vhost
                );
            }

            $this->channel = $this->connection->channel();
        }
    }

    /**
     * Envia uma mensagem para a fila especificada
     *
     * @param string $queueName Nome da fila
     * @param mixed $message Mensagem a ser enviada (será convertida para JSON se for array/object)
     * @param array $properties Propriedades adicionais da mensagem (opcional)
     * @return bool
     */
    public function sendMessage(string $queueName, $message, array $properties = []): bool
    {
        try {
            $this->connect();

            // Declara a fila (se não existir, será criada)
            $this->channel->queue_declare(
                $queueName,
                false,    // passive
                true,     // durable - fila persiste após restart
                false,    // exclusive
                false     // auto_delete
            );

            // Converte a mensagem para string se necessário
            if (is_array($message) || is_object($message)) {
                $message = json_encode($message);
            }

            // Propriedades padrão da mensagem
            $defaultProperties = [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT, // Mensagem persistente
                'content_type' => 'application/json',
            ];

            $messageProperties = array_merge($defaultProperties, $properties);

            // Cria a mensagem
            $msg = new AMQPMessage($message, $messageProperties);

            // Envia a mensagem para a fila
            $this->channel->basic_publish($msg, '', $queueName);

            Log::info("Mensagem enviada para a fila: {$queueName}", [
                'queue' => $queueName,
                'message' => $message
            ]);

            return true;
        } catch (Exception $e) {
            Log::error("Erro ao enviar mensagem para RabbitMQ: {$e->getMessage()}", [
                'queue' => $queueName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Envia múltiplas mensagens para a mesma fila
     *
     * @param string $queueName Nome da fila
     * @param array $messages Array de mensagens
     * @return int Número de mensagens enviadas com sucesso
     */
    public function sendBatch(string $queueName, array $messages): int
    {
        $successCount = 0;

        foreach ($messages as $message) {
            if ($this->sendMessage($queueName, $message)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Fecha a conexão com o RabbitMQ
     */
    public function close(): void
    {
        try {
            if ($this->channel !== null) {
                $this->channel->close();
            }

            if ($this->connection !== null && $this->connection->isConnected()) {
                $this->connection->close();
            }
        } catch (Exception $e) {
            Log::error("Erro ao fechar conexão com RabbitMQ: {$e->getMessage()}");
        }
    }

    /**
     * Destrutor - garante que a conexão seja fechada
     */
    public function __destruct()
    {
        $this->close();
    }
}
