<?php

namespace app\domain\Task;

use think\Model;
use app\domain\Task\ValueObject\TaskStatus;
use app\domain\Task\ValueObject\ProcessType;

class Task extends Model
{
    protected $table = 'tasks';
    
    // Auto write timestamp
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    // Field types
    protected $type = [
        'id' => 'integer',
        'task_number' => 'string',
        'user_id' => 'integer',
        'status' => 'string',
        'process_type' => 'string',
        'priority' => 'integer',
        'total_files' => 'integer',
        'processed_files' => 'integer',
        'current_step' => 'string',
        'progress' => 'float',
        'result_data' => 'json',
        'error_message' => 'string',
        'retry_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
    
    // JSON fields
    protected $json = ['result_data'];
    
    // Default values
    protected $default = [
        'status' => TaskStatus::PENDING,
        'priority' => 2,
        'total_files' => 0,
        'processed_files' => 0,
        'progress' => 0,
        'retry_count' => 0,
    ];
    
    /**
     * Generate task number
     * Format: UserID_YYYYMMDD_Serial
     */
    public static function generateTaskNumber(int $userId): string
    {
        $date = date('Ymd');
        $prefix = "{$userId}_{$date}_";
        
        // Get today's max serial number
        $lastTask = self::where('task_number', 'like', $prefix . '%')
            ->order('id', 'desc')
            ->find();
            
        $serial = 1;
        if ($lastTask) {
            $lastNumber = $lastTask->task_number;
            $parts = explode('_', $lastNumber);
            $serial = intval(end($parts)) + 1;
        }
        
        return $prefix . str_pad($serial, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Task files relationship
     */
    public function files()
    {
        return $this->hasMany(TaskFile::class, 'task_id');
    }
    
    /**
     * Processing results relationship
     */
    public function results()
    {
        return $this->hasMany(ProcessingResult::class, 'task_id');
    }
    
    /**
     * Update task progress
     */
    public function updateProgress(): void
    {
        if ($this->total_files > 0) {
            $this->progress = round(($this->processed_files / $this->total_files) * 100, 2);
            $this->save();
        }
    }
    
    /**
     * Check if task can be retried
     */
    public function canRetry(): bool
    {
        return $this->status === TaskStatus::FAILED && $this->retry_count < 3;
    }
    
    /**
     * Mark task as started
     */
    public function markAsStarted(): void
    {
        $this->status = TaskStatus::PROCESSING;
        $this->started_at = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Mark task as completed
     */
    public function markAsCompleted(): void
    {
        $this->status = TaskStatus::COMPLETED;
        $this->completed_at = date('Y-m-d H:i:s');
        $this->progress = 100;
        $this->save();
    }
    
    /**
     * Mark task as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->status = TaskStatus::FAILED;
        $this->error_message = $errorMessage;
        $this->save();
    }
}