<?php

namespace app\domain\Task;

use think\Model;

class ProcessingResult extends Model
{
    protected $table = 'processing_results';
    
    // Auto write timestamp
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    // Field types
    protected $type = [
        'id' => 'integer',
        'task_id' => 'integer',
        'task_file_id' => 'integer',
        'result_type' => 'string',
        'result_path' => 'string',
        'result_data' => 'json',
        'file_size' => 'integer',
        'duration' => 'float',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // JSON fields
    protected $json = ['result_data', 'metadata'];
    
    // Default values
    protected $default = [
        'result_data' => '{}',
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
     * Task file relationship
     */
    public function taskFile()
    {
        return $this->belongsTo(TaskFile::class, 'task_file_id');
    }
    
    /**
     * Get result by type for a task
     */
    public static function getByType(int $taskId, string $type)
    {
        return self::where('task_id', $taskId)
            ->where('result_type', $type)
            ->order('id', 'desc')
            ->find();
    }
    
    /**
     * Store transcription result
     */
    public static function storeTranscription(int $taskId, int $fileId, array $data): self
    {
        $result = new self();
        $result->task_id = $taskId;
        $result->task_file_id = $fileId;
        $result->result_type = 'transcription';
        $result->result_data = $data;
        $result->save();
        
        return $result;
    }
    
    /**
     * Store audio extraction result
     */
    public static function storeAudioExtraction(int $taskId, int $fileId, string $path, array $metadata = []): self
    {
        $result = new self();
        $result->task_id = $taskId;
        $result->task_file_id = $fileId;
        $result->result_type = 'audio_extraction';
        $result->result_path = $path;
        $result->metadata = $metadata;
        $result->file_size = filesize($path) ?: 0;
        $result->save();
        
        return $result;
    }
}