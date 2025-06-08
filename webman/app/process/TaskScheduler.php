<?php

namespace app\process;

use Workerman\Timer;
use app\infrastructure\Repository\TaskRepository;
use app\infrastructure\Service\QueueService;
use app\domain\Task\ValueObject\ProcessType;
use app\domain\Event\DomainEvent;
use support\Log;

class TaskScheduler
{
    private TaskRepository $taskRepository;
    private QueueService $queueService;
    
    /**
     * Process configuration
     */
    public static function getConfig(): array
    {
        return [
            'name' => 'TaskScheduler',
            'count' => 1,
            'constructor' => [],
        ];
    }
    
    /**
     * Called when process starts
     */
    public function onWorkerStart()
    {
        $this->taskRepository = new TaskRepository();
        $this->queueService = new QueueService();
        
        // Check pending tasks every 30 seconds
        Timer::add(30, function() {
            $this->checkPendingTasks();
        });
        
        // Check failed tasks every 5 minutes (300 seconds)
        Timer::add(300, function() {
            $this->checkFailedTasks();
        });
        
        // Initial check
        $this->checkPendingTasks();
        
        Log::info('TaskScheduler process started');
    }
    
    /**
     * Check and dispatch pending tasks
     */
    private function checkPendingTasks(): void
    {
        try {
            $pendingTasks = $this->taskRepository->getPendingTasks(50);
            
            if (empty($pendingTasks)) {
                return;
            }
            
            Log::info('Found ' . count($pendingTasks) . ' pending tasks');
            
            foreach ($pendingTasks as $taskData) {
                try {
                    $routingKey = $this->getRoutingKey($taskData['process_type']);
                    
                    $this->queueService->publish($routingKey, [
                        'task_id' => $taskData['id'],
                        'task_number' => $taskData['task_number'],
                        'process_type' => $taskData['process_type'],
                        'priority' => $taskData['priority'],
                        'retry_count' => $taskData['retry_count'],
                    ]);
                    
                    Log::info("Dispatched task {$taskData['task_number']} to queue {$routingKey}");
                    
                } catch (\Exception $e) {
                    Log::error("Failed to dispatch task {$taskData['task_number']}: " . $e->getMessage());
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error checking pending tasks: ' . $e->getMessage());
        }
    }
    
    /**
     * Check and retry failed tasks
     */
    private function checkFailedTasks(): void
    {
        try {
            $failedTasks = $this->taskRepository->getFailedTasksForRetry(5, 20);
            
            if (empty($failedTasks)) {
                return;
            }
            
            Log::info('Found ' . count($failedTasks) . ' failed tasks for retry');
            
            foreach ($failedTasks as $taskData) {
                try {
                    // Update task status
                    $this->taskRepository->update($taskData['id'], [
                        'status' => 'pending',
                        'retry_count' => $taskData['retry_count'] + 1,
                        'error_message' => null,
                    ]);
                    
                    // Record retry event
                    DomainEvent::record(
                        $taskData['task_number'],
                        'Task',
                        DomainEvent::TASK_RETRIED,
                        [
                            'retry_count' => $taskData['retry_count'] + 1,
                            'previous_error' => $taskData['error_message'],
                        ]
                    );
                    
                    // Dispatch to queue
                    $routingKey = $this->getRoutingKey($taskData['process_type']);
                    
                    $this->queueService->publish($routingKey, [
                        'task_id' => $taskData['id'],
                        'task_number' => $taskData['task_number'],
                        'process_type' => $taskData['process_type'],
                        'priority' => $taskData['priority'],
                        'retry_count' => $taskData['retry_count'] + 1,
                    ]);
                    
                    Log::info("Retrying task {$taskData['task_number']} (attempt " . ($taskData['retry_count'] + 1) . ")");
                    
                } catch (\Exception $e) {
                    Log::error("Failed to retry task {$taskData['task_number']}: " . $e->getMessage());
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error checking failed tasks: ' . $e->getMessage());
        }
    }
    
    /**
     * Get routing key by process type
     */
    private function getRoutingKey(string $processType): string
    {
        $mapping = [
            ProcessType::AUDIO_EXTRACT => 'audio_extract',
            ProcessType::DENOISE => 'denoise',
            ProcessType::FAST_RECOGNITION => 'fast_recognition',
            ProcessType::TRANSCRIPTION => 'transcription',
            ProcessType::FULL_PROCESS => 'audio_extract',
        ];
        
        return $mapping[$processType] ?? 'audio_extract';
    }
}