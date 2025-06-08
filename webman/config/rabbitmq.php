<?php

return [
    'default' => [
        'host' => getenv('RABBITMQ_HOST') ?: 'localhost',
        'port' => getenv('RABBITMQ_PORT') ?: 5672,
        'user' => getenv('RABBITMQ_USER') ?: 'guest',
        'password' => getenv('RABBITMQ_PASSWORD') ?: 'guest',
        'vhost' => getenv('RABBITMQ_VHOST') ?: '/',
        'connection_timeout' => 3.0,
        'read_write_timeout' => 130.0,
        'heartbeat' => 60,
    ],
    
    // Exchange configuration
    'exchanges' => [
        'audio_processing' => [
            'name' => 'audio_processing',
            'type' => 'direct',
            'durable' => true,
            'auto_delete' => false,
        ],
        'audio_processing_dlx' => [
            'name' => 'audio_processing_dlx',
            'type' => 'direct',
            'durable' => true,
            'auto_delete' => false,
        ],
    ],
    
    // Queue configuration
    'queues' => [
        'audio_extract' => [
            'name' => 'audio_extract',
            'durable' => true,
            'auto_delete' => false,
            'arguments' => [
                'x-message-ttl' => 3600000, // 1 hour
                'x-dead-letter-exchange' => 'audio_processing_dlx',
                'x-dead-letter-routing-key' => 'audio_extract_failed',
            ],
            'routing_key' => 'audio_extract',
            'priority' => 8,
        ],
        'denoise' => [
            'name' => 'denoise',
            'durable' => true,
            'auto_delete' => false,
            'arguments' => [
                'x-message-ttl' => 3600000,
                'x-dead-letter-exchange' => 'audio_processing_dlx',
                'x-dead-letter-routing-key' => 'denoise_failed',
            ],
            'routing_key' => 'denoise',
            'priority' => 6,
        ],
        'fast_recognition' => [
            'name' => 'fast_recognition',
            'durable' => true,
            'auto_delete' => false,
            'arguments' => [
                'x-message-ttl' => 3600000,
                'x-dead-letter-exchange' => 'audio_processing_dlx',
                'x-dead-letter-routing-key' => 'fast_recognition_failed',
            ],
            'routing_key' => 'fast_recognition',
            'priority' => 4,
        ],
        'transcription' => [
            'name' => 'transcription',
            'durable' => true,
            'auto_delete' => false,
            'arguments' => [
                'x-message-ttl' => 3600000,
                'x-dead-letter-exchange' => 'audio_processing_dlx',
                'x-dead-letter-routing-key' => 'transcription_failed',
            ],
            'routing_key' => 'transcription',
            'priority' => 2,
        ],
    ],
    
    // Dead letter queues
    'dead_letter_queues' => [
        'audio_extract_failed' => [
            'name' => 'audio_extract_failed',
            'durable' => true,
            'routing_key' => 'audio_extract_failed',
        ],
        'denoise_failed' => [
            'name' => 'denoise_failed',
            'durable' => true,
            'routing_key' => 'denoise_failed',
        ],
        'fast_recognition_failed' => [
            'name' => 'fast_recognition_failed',
            'durable' => true,
            'routing_key' => 'fast_recognition_failed',
        ],
        'transcription_failed' => [
            'name' => 'transcription_failed',
            'durable' => true,
            'routing_key' => 'transcription_failed',
        ],
    ],
];