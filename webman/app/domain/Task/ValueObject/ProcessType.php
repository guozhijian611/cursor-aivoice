<?php

namespace app\domain\Task\ValueObject;

class ProcessType
{
    public const AUDIO_EXTRACT = 'audio_extract';
    public const DENOISE = 'denoise';
    public const FAST_RECOGNITION = 'fast_recognition';
    public const TRANSCRIPTION = 'transcription';
    public const FULL_PROCESS = 'full_process';
    
    /**
     * Get all process types
     */
    public static function all(): array
    {
        return [
            self::AUDIO_EXTRACT,
            self::DENOISE,
            self::FAST_RECOGNITION,
            self::TRANSCRIPTION,
            self::FULL_PROCESS,
        ];
    }
    
    /**
     * Get priority by process type
     */
    public static function getPriority(string $type): int
    {
        $priorities = [
            self::AUDIO_EXTRACT => 8,
            self::DENOISE => 6,
            self::FAST_RECOGNITION => 4,
            self::TRANSCRIPTION => 2,
            self::FULL_PROCESS => 2,
        ];
        
        return $priorities[$type] ?? 2;
    }
}