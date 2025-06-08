<?php

namespace app\infrastructure\Service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use app\domain\Task\Task;

class QueueService
{
    private ?AMQPStreamConnection $connection = null;
    private ?object $channel = null;
    private array $config;
    
    public function __construct()
    {
        $this->config = config('rabbitmq');
    }
    
    /**
     * Get RabbitMQ connection
     */
    private function getConnection(): AMQPStreamConnection
    {
        if (!$this->connection) {
            $config = $this->config['default'];
            $this->connection = new AMQPStreamConnection(
                $config['host'],
                $config['port'],
                $config['user'],
                $config['password'],
                $config['vhost'],
                false,
                'AMQPLAIN',
                null,
                'en_US',
                $config['connection_timeout'],
                $config['read_write_timeout'],
                null,
                false,
                $config['heartbeat']
            );
        }
        
        return $this->connection;
    }
    
    /**
     * Get channel
     */
    private function getChannel()
    {
        if (!$this->channel) {
            $this->channel = $this->getConnection()->channel();
            $this->declareExchangesAndQueues();
        }
        
        return $this->channel;
    }
    
    /**
     * Declare exchanges and queues
     */
    private function declareExchangesAndQueues(): void
    {
        $channel = $this->channel;
        
        // Declare exchanges
        foreach ($this->config['exchanges'] as $exchange) {
            $channel->exchange_declare(
                $exchange['name'],
                $exchange['type'],
                false,
                $exchange['durable'],
                $exchange['auto_delete']
            );
        }
        
        // Declare queues and bind them
        foreach ($this->config['queues'] as $queue) {
            $channel->queue_declare(
                $queue['name'],
                false,
                $queue['durable'],
                false,
                $queue['auto_delete'],
                false,
                $queue['arguments'] ?? []
            );
            
            $channel->queue_bind(
                $queue['name'],
                $this->config['exchanges']['audio_processing']['name'],
                $queue['routing_key']
            );
        }
        
        // Declare dead letter queues
        foreach ($this->config['dead_letter_queues'] as $dlq) {
            $channel->queue_declare(
                $dlq['name'],
                false,
                $dlq['durable'],
                false,
                false
            );
            
            $channel->queue_bind(
                $dlq['name'],
                $this->config['exchanges']['audio_processing_dlx']['name'],
                $dlq['routing_key']
            );
        }
    }
    
    /**
     * Publish message to queue
     */
    public function publish(string $routingKey, array $data): void
    {
        $channel = $this->getChannel();
        
        $message = new AMQPMessage(
            json_encode($data),
            [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'content_type' => 'application/json',
                'timestamp' => time(),
            ]
        );
        
        $channel->basic_publish(
            $message,
            $this->config['exchanges']['audio_processing']['name'],
            $routingKey
        );
    }
    
    /**
     * Remove task from queue (for cancellation)
     */
    public function removeFromQueue(Task $task): bool
    {
        // Note: RabbitMQ doesn't support removing specific messages from queue
        // This would require implementing a cancellation flag check in consumers
        // For now, we'll rely on status checking in consumers
        return true;
    }
    
    /**
     * Get queue statistics
     */
    public function getQueueStats(string $queueName): array
    {
        try {
            $channel = $this->getChannel();
            list($queueName, $messageCount, $consumerCount) = $channel->queue_declare(
                $queueName,
                true // passive mode to just get info
            );
            
            return [
                'name' => $queueName,
                'messages' => $messageCount,
                'consumers' => $consumerCount,
            ];
        } catch (\Exception $e) {
            return [
                'name' => $queueName,
                'messages' => 0,
                'consumers' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Close connections
     */
    public function __destruct()
    {
        if ($this->channel) {
            $this->channel->close();
        }
        
        if ($this->connection) {
            $this->connection->close();
        }
    }
}