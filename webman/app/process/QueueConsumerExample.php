<?php

namespace app\process;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use app\infrastructure\Repository\TaskRepository;
use app\infrastructure\Service\ProgressService;
use app\domain\Event\DomainEvent;
use app\domain\Task\Task;
use support\Log;

/**
 * Example Queue Consumer for Audio Processing
 * 
 * This is a demonstration of how to implement queue consumers.
 * In production, you would have separate consumers for each queue type.
 */
class QueueConsumerExample
{
    private AMQPStreamConnection $connection;
    private $channel;
    private TaskRepository $taskRepository;
    private ProgressService $progressService;
    private string $queueName;
    
    /**
     * Process configuration
     */
    public static function getConfig(): array
    {
        return [
            'name' => 'QueueConsumerExample',
            'count' => 0, // Set to 0 to disable by default
            'constructor' => ['audio_extract'], // Queue name to consume
        ];
    }
    
    public function __construct(string $queueName = 'audio_extract')
    {
        $this->queueName = $queueName;
    }
    
    /**
     * Called when process starts
     */
    public function onWorkerStart()
    {
        $this->taskRepository = new TaskRepository();
        $this->progressService = new ProgressService();
        
        // Connect to RabbitMQ
        $config = config('rabbitmq.default');
        $this->connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost']
        );
        
        $this->channel = $this->connection->channel();
        
        // Set prefetch count
        $this->channel->basic_qos(null, 1, null);
        
        // Start consuming
        $this->channel->basic_consume(
            $this->queueName,
            '',
            false,
            false,
            false,
            false,
            [$this, 'processMessage']
        );
        
        Log::info("Queue consumer started for queue: {$this->queueName}");
        
        // Keep consuming
        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }
    
    /**
     * Process a message from the queue
     */
    public function processMessage(AMQPMessage $message): void
    {
        $startTime = microtime(true);
        
        try {
            $data = json_decode($message->body, true);
            
            if (!$data || !isset($data['task_id'])) {
                throw new \Exception('Invalid message format');
            }
            
            $taskId = $data['task_id'];
            $task = $this->taskRepository->findById($taskId);
            
            if (!$task) {
                throw new \Exception("Task not found: {$taskId}");
            }
            
            // Check if task is cancelled
            if ($task->status === 'cancelled') {
                Log::info("Task {$task->task_number} is cancelled, skipping");
                $this->channel->basic_ack($message->delivery_info['delivery_tag']);
                return;
            }
            
            // Update task status
            $task->markAsStarted();
            
            // Update progress
            $this->progressService->updateProgress($task->id, [
                'status' => 'processing',
                'current_step' => $this->queueName,
                'message' => "Processing in {$this->queueName} queue",
            ]);
            
            // Simulate processing based on queue type
            $this->simulateProcessing($task);
            
            // Mark task as completed (in real implementation, this would depend on the workflow)
            if ($this->shouldCompleteTask($task)) {
                $task->markAsCompleted();
                $this->progressService->markCompleted($task->id);
            }
            
            // Record success event
            DomainEvent::record(
                $task->task_number,
                'Task',
                DomainEvent::QUEUE_JOB_PROCESSED,
                [
                    'queue' => $this->queueName,
                    'duration' => microtime(true) - $startTime,
                ]
            );
            
            // Acknowledge message
            $this->channel->basic_ack($message->delivery_info['delivery_tag']);
            
            Log::info("Successfully processed task {$task->task_number} in {$this->queueName}");
            
        } catch (\Exception $e) {
            Log::error("Error processing message in {$this->queueName}: " . $e->getMessage());
            
            // Update task status if possible
            if (isset($task)) {
                $task->markAsFailed($e->getMessage());
                $this->progressService->markFailed($task->id, $e->getMessage());
            }
            
            // Reject message and send to DLQ
            $this->channel->basic_nack($message->delivery_info['delivery_tag'], false, false);
        }
    }
    
    /**
     * Simulate processing based on queue type
     */
    private function simulateProcessing(Task $task): void
    {
        switch ($this->queueName) {
            case 'audio_extract':
                Log::info("Simulating audio extraction for task {$task->task_number}");
                sleep(2); // Simulate processing time
                break;
                
            case 'denoise':
                Log::info("Simulating denoising for task {$task->task_number}");
                sleep(3);
                break;
                
            case 'fast_recognition':
                Log::info("Simulating fast recognition for task {$task->task_number}");
                sleep(1);
                break;
                
            case 'transcription':
                Log::info("Simulating transcription for task {$task->task_number}");
                sleep(5);
                break;
        }
        
        // Update progress
        for ($i = 20; $i <= 100; $i += 20) {
            $this->progressService->updateProgress($task->id, [
                'progress' => $i,
                'message' => "Processing... {$i}%",
            ]);
            usleep(200000); // 200ms delay
        }
    }
    
    /**
     * Determine if task should be marked as completed
     */
    private function shouldCompleteTask(Task $task): bool
    {
        // In a real implementation, this would check if all steps are done
        // For now, we'll complete after transcription or if it's not a full process
        return $this->queueName === 'transcription' || 
               $task->process_type !== 'full_process';
    }
    
    /**
     * Called when process stops
     */
    public function onWorkerStop()
    {
        if ($this->channel) {
            $this->channel->close();
        }
        
        if ($this->connection) {
            $this->connection->close();
        }
        
        Log::info("Queue consumer stopped for queue: {$this->queueName}");
    }
}