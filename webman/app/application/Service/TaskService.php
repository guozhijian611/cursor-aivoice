<?php

namespace app\application\Service;

use app\domain\Task\Task;
use app\domain\Task\TaskFile;
use app\domain\Task\ValueObject\TaskStatus;
use app\domain\Task\ValueObject\ProcessType;
use app\domain\Event\DomainEvent;
use app\infrastructure\Repository\TaskRepository;
use app\infrastructure\Service\QueueService;
use app\infrastructure\Service\FileStorageService;
use app\infrastructure\Service\ProgressService;
use think\facade\Db;

class TaskService
{
    private TaskRepository $taskRepository;
    private QueueService $queueService;
    private FileStorageService $fileStorageService;
    private ProgressService $progressService;
    
    public function __construct()
    {
        $this->taskRepository = new TaskRepository();
        $this->queueService = new QueueService();
        $this->fileStorageService = new FileStorageService();
        $this->progressService = new ProgressService();
    }
    
    /**
     * Create new task
     */
    public function createTask(int $userId, string $processType, array $files): array
    {
        try {
            // Check daily limit
            if ($this->taskRepository->hasReachedDailyLimit($userId)) {
                throw new \Exception('Daily task limit reached');
            }
            
            // Validate process type
            if (!in_array($processType, ProcessType::all())) {
                throw new \Exception('Invalid process type');
            }
            
            Db::startTrans();
            
            // Create task
            $task = $this->taskRepository->create([
                'user_id' => $userId,
                'process_type' => $processType,
                'priority' => ProcessType::getPriority($processType),
                'total_files' => count($files),
                'status' => TaskStatus::PENDING,
            ]);
            
            // Process and store files
            foreach ($files as $file) {
                $storedPath = $this->fileStorageService->store($file, $task->task_number);
                
                $taskFile = new TaskFile();
                $taskFile->save([
                    'task_id' => $task->id,
                    'original_filename' => $file['name'],
                    'stored_path' => $storedPath,
                    'file_type' => $this->detectFileType($file['name']),
                    'file_size' => $file['size'],
                    'mime_type' => $file['type'],
                    'status' => 'pending',
                ]);
            }
            
            // Record domain event
            DomainEvent::record(
                $task->task_number,
                'Task',
                DomainEvent::TASK_CREATED,
                [
                    'user_id' => $userId,
                    'process_type' => $processType,
                    'file_count' => count($files),
                ],
                $userId
            );
            
            // Dispatch to queue based on process type
            $this->dispatchToQueue($task);
            
            Db::commit();
            
            return [
                'success' => true,
                'task_id' => $task->id,
                'task_number' => $task->task_number,
                'message' => 'Task created successfully',
            ];
            
        } catch (\Exception $e) {
            Db::rollback();
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get task details
     */
    public function getTaskDetails(string $taskNumber, ?int $userId = null): ?array
    {
        $task = $this->taskRepository->findByTaskNumber($taskNumber);
        
        if (!$task) {
            return null;
        }
        
        // Check if user has permission
        if ($userId && $task->user_id !== $userId) {
            return null;
        }
        
        // Get progress from Redis
        $progress = $this->progressService->getProgress($task->id);
        
        return [
            'id' => $task->id,
            'task_number' => $task->task_number,
            'status' => $task->status,
            'process_type' => $task->process_type,
            'progress' => $progress ?: $task->progress,
            'current_step' => $task->current_step,
            'total_files' => $task->total_files,
            'processed_files' => $task->processed_files,
            'files' => $task->files()->select()->toArray(),
            'results' => $task->results()->select()->toArray(),
            'created_at' => $task->created_at,
            'started_at' => $task->started_at,
            'completed_at' => $task->completed_at,
            'error_message' => $task->error_message,
        ];
    }
    
    /**
     * Cancel task
     */
    public function cancelTask(string $taskNumber, int $userId): bool
    {
        $task = $this->taskRepository->findByTaskNumber($taskNumber);
        
        if (!$task || $task->user_id !== $userId) {
            return false;
        }
        
        if (!in_array($task->status, [TaskStatus::PENDING, TaskStatus::PROCESSING])) {
            return false;
        }
        
        $task->status = TaskStatus::CANCELLED;
        $task->save();
        
        // Record event
        DomainEvent::record(
            $task->task_number,
            'Task',
            DomainEvent::TASK_CANCELLED,
            ['user_id' => $userId],
            $userId
        );
        
        // Remove from queue
        $this->queueService->removeFromQueue($task);
        
        return true;
    }
    
    /**
     * Retry failed task
     */
    public function retryTask(string $taskNumber, int $userId): bool
    {
        $task = $this->taskRepository->findByTaskNumber($taskNumber);
        
        if (!$task || $task->user_id !== $userId) {
            return false;
        }
        
        if (!$task->canRetry()) {
            return false;
        }
        
        $task->status = TaskStatus::PENDING;
        $task->retry_count += 1;
        $task->error_message = null;
        $task->save();
        
        // Record event
        DomainEvent::record(
            $task->task_number,
            'Task',
            DomainEvent::TASK_RETRIED,
            [
                'retry_count' => $task->retry_count,
                'user_id' => $userId,
            ],
            $userId
        );
        
        // Re-dispatch to queue
        $this->dispatchToQueue($task);
        
        return true;
    }
    
    /**
     * Dispatch task to appropriate queue
     */
    private function dispatchToQueue(Task $task): void
    {
        $routingKey = $this->getRoutingKey($task->process_type);
        
        $this->queueService->publish($routingKey, [
            'task_id' => $task->id,
            'task_number' => $task->task_number,
            'process_type' => $task->process_type,
            'priority' => $task->priority,
            'retry_count' => $task->retry_count,
        ]);
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
            ProcessType::FULL_PROCESS => 'audio_extract', // Start with extraction
        ];
        
        return $mapping[$processType] ?? 'audio_extract';
    }
    
    /**
     * Detect file type from filename
     */
    private function detectFileType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'];
        $audioExtensions = ['mp3', 'wav', 'flac', 'aac', 'ogg', 'wma', 'm4a'];
        
        if (in_array($extension, $videoExtensions)) {
            return 'video';
        }
        
        if (in_array($extension, $audioExtensions)) {
            return 'audio';
        }
        
        return 'unknown';
    }
}