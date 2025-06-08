<?php

namespace app\domain\Task\ValueObject;

class TaskStatus
{
    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';
    
    /**
     * Get all status values
     */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::PROCESSING,
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
        ];
    }
    
    /**
     * Check if status is valid
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, self::all());
    }
}