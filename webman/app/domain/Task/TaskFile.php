<?php

namespace app\domain\Task;

use think\Model;

class TaskFile extends Model
{
    protected $table = 'task_files';
    
    // Auto write timestamp
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    // Field types
    protected $type = [
        'id' => 'integer',
        'task_id' => 'integer',
        'original_filename' => 'string',
        'stored_path' => 'string',
        'file_type' => 'string',
        'file_size' => 'integer',
        'mime_type' => 'string',
        'status' => 'string',
        'processing_step' => 'string',
        'processed_path' => 'string',
        'error_message' => 'string',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // JSON fields
    protected $json = ['metadata'];
    
    // Default values
    protected $default = [
        'status' => 'pending',
        'metadata' => '{}',
    ];
    
    /**
     * Task relationship
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
    
    /**
     * Update file status
     */
    public function updateStatus(string $status, ?string $step = null): void
    {
        $this->status = $status;
        if ($step) {
            $this->processing_step = $step;
        }
        $this->save();
    }
    
    /**
     * Mark as processed
     */
    public function markAsProcessed(string $processedPath): void
    {
        $this->status = 'completed';
        $this->processed_path = $processedPath;
        $this->save();
    }
    
    /**
     * Mark as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->status = 'failed';
        $this->error_message = $errorMessage;
        $this->save();
    }
    
    /**
     * Get file extension
     */
    public function getExtension(): string
    {
        return strtolower(pathinfo($this->original_filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Check if file is video
     */
    public function isVideo(): bool
    {
        $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'];
        return in_array($this->getExtension(), $videoExtensions);
    }
    
    /**
     * Check if file is audio
     */
    public function isAudio(): bool
    {
        $audioExtensions = ['mp3', 'wav', 'flac', 'aac', 'ogg', 'wma', 'm4a'];
        return in_array($this->getExtension(), $audioExtensions);
    }
}