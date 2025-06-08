<?php

namespace app\infrastructure\Service;

use Predis\Client as RedisClient;

class ProgressService
{
    private RedisClient $redis;
    private string $prefix = 'progress:';
    private int $defaultExpire = 86400; // 24 hours
    
    public function __construct()
    {
        $config = config('redis.progress');
        $this->redis = new RedisClient([
            'scheme' => 'tcp',
            'host' => $config['host'],
            'port' => $config['port'],
            'password' => $config['auth'] ?: null,
            'database' => $config['db'],
        ]);
        
        if (isset($config['prefix'])) {
            $this->prefix = $config['prefix'];
        }
        
        if (isset($config['expire'])) {
            $this->defaultExpire = $config['expire'];
        }
    }
    
    /**
     * Update task progress
     */
    public function updateProgress(int $taskId, array $data): void
    {
        $key = $this->prefix . $taskId;
        
        $progress = [
            'task_id' => $taskId,
            'progress' => $data['progress'] ?? 0,
            'current_step' => $data['current_step'] ?? '',
            'processed_files' => $data['processed_files'] ?? 0,
            'total_files' => $data['total_files'] ?? 0,
            'status' => $data['status'] ?? 'processing',
            'message' => $data['message'] ?? '',
            'updated_at' => time(),
        ];
        
        $this->redis->setex($key, $this->defaultExpire, json_encode($progress));
        
        // Publish to channel for real-time updates
        $this->publishProgress($taskId, $progress);
    }
    
    /**
     * Get task progress
     */
    public function getProgress(int $taskId): ?array
    {
        $key = $this->prefix . $taskId;
        $data = $this->redis->get($key);
        
        if (!$data) {
            return null;
        }
        
        return json_decode($data, true);
    }
    
    /**
     * Update step progress
     */
    public function updateStep(int $taskId, string $step, float $stepProgress): void
    {
        $progress = $this->getProgress($taskId) ?? [];
        
        $progress['current_step'] = $step;
        $progress['step_progress'] = $stepProgress;
        $progress['updated_at'] = time();
        
        $key = $this->prefix . $taskId;
        $this->redis->setex($key, $this->defaultExpire, json_encode($progress));
        
        $this->publishProgress($taskId, $progress);
    }
    
    /**
     * Increment processed files
     */
    public function incrementProcessedFiles(int $taskId): void
    {
        $progress = $this->getProgress($taskId) ?? [];
        
        $processedFiles = ($progress['processed_files'] ?? 0) + 1;
        $totalFiles = $progress['total_files'] ?? 1;
        
        $progress['processed_files'] = $processedFiles;
        $progress['progress'] = round(($processedFiles / $totalFiles) * 100, 2);
        $progress['updated_at'] = time();
        
        $key = $this->prefix . $taskId;
        $this->redis->setex($key, $this->defaultExpire, json_encode($progress));
        
        $this->publishProgress($taskId, $progress);
    }
    
    /**
     * Mark task as completed
     */
    public function markCompleted(int $taskId): void
    {
        $progress = $this->getProgress($taskId) ?? [];
        
        $progress['status'] = 'completed';
        $progress['progress'] = 100;
        $progress['completed_at'] = time();
        $progress['updated_at'] = time();
        
        $key = $this->prefix . $taskId;
        $this->redis->setex($key, $this->defaultExpire, json_encode($progress));
        
        $this->publishProgress($taskId, $progress);
    }
    
    /**
     * Mark task as failed
     */
    public function markFailed(int $taskId, string $error): void
    {
        $progress = $this->getProgress($taskId) ?? [];
        
        $progress['status'] = 'failed';
        $progress['error'] = $error;
        $progress['failed_at'] = time();
        $progress['updated_at'] = time();
        
        $key = $this->prefix . $taskId;
        $this->redis->setex($key, $this->defaultExpire, json_encode($progress));
        
        $this->publishProgress($taskId, $progress);
    }
    
    /**
     * Delete progress data
     */
    public function deleteProgress(int $taskId): void
    {
        $key = $this->prefix . $taskId;
        $this->redis->del([$key]);
    }
    
    /**
     * Publish progress update for real-time notifications
     */
    private function publishProgress(int $taskId, array $progress): void
    {
        $channel = 'task_progress:' . $taskId;
        $this->redis->publish($channel, json_encode($progress));
    }
    
    /**
     * Subscribe to task progress updates
     */
    public function subscribeToProgress(int $taskId, callable $callback): void
    {
        $channel = 'task_progress:' . $taskId;
        $pubsub = $this->redis->pubSubLoop();
        
        $pubsub->subscribe($channel);
        
        foreach ($pubsub as $message) {
            if ($message->kind === 'message') {
                $data = json_decode($message->payload, true);
                $callback($data);
            }
        }
    }
    
    /**
     * Get multiple task progress
     */
    public function getMultipleProgress(array $taskIds): array
    {
        $keys = array_map(fn($id) => $this->prefix . $id, $taskIds);
        $values = $this->redis->mget($keys);
        
        $result = [];
        foreach ($taskIds as $index => $taskId) {
            if ($values[$index]) {
                $result[$taskId] = json_decode($values[$index], true);
            }
        }
        
        return $result;
    }
}