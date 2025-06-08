<?php

namespace app\domain\Event;

use think\Model;

class DomainEvent extends Model
{
    protected $table = 'domain_events';
    
    // Auto write timestamp
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false;
    
    // Field types
    protected $type = [
        'id' => 'integer',
        'aggregate_id' => 'string',
        'aggregate_type' => 'string',
        'event_type' => 'string',
        'event_data' => 'json',
        'user_id' => 'integer',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];
    
    // JSON fields
    protected $json = ['event_data'];
    
    // Default values
    protected $default = [
        'event_data' => '{}',
    ];
    
    /**
     * Record a new domain event
     */
    public static function record(
        string $aggregateId,
        string $aggregateType,
        string $eventType,
        array $eventData,
        ?int $userId = null
    ): self {
        $event = new self();
        $event->aggregate_id = $aggregateId;
        $event->aggregate_type = $aggregateType;
        $event->event_type = $eventType;
        $event->event_data = $eventData;
        $event->user_id = $userId;
        $event->occurred_at = date('Y-m-d H:i:s');
        $event->save();
        
        return $event;
    }
    
    /**
     * Get events for an aggregate
     */
    public static function getForAggregate(string $aggregateId, string $aggregateType): array
    {
        return self::where('aggregate_id', $aggregateId)
            ->where('aggregate_type', $aggregateType)
            ->order('occurred_at', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * Common event types
     */
    public const TASK_CREATED = 'task.created';
    public const TASK_STARTED = 'task.started';
    public const TASK_COMPLETED = 'task.completed';
    public const TASK_FAILED = 'task.failed';
    public const TASK_CANCELLED = 'task.cancelled';
    public const TASK_RETRIED = 'task.retried';
    
    public const FILE_UPLOADED = 'file.uploaded';
    public const FILE_PROCESSING_STARTED = 'file.processing_started';
    public const FILE_PROCESSING_COMPLETED = 'file.processing_completed';
    public const FILE_PROCESSING_FAILED = 'file.processing_failed';
    
    public const QUEUE_JOB_CREATED = 'queue.job_created';
    public const QUEUE_JOB_PROCESSED = 'queue.job_processed';
    public const QUEUE_JOB_FAILED = 'queue.job_failed';
}