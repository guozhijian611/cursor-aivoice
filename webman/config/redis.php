<?php

return [
    'default' => [
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => getenv('REDIS_PORT') ?: 6379,
        'auth' => getenv('REDIS_PASSWORD') ?: '',
        'db' => 0,
        'prefix' => 'audio_processing:',
        'options' => [
            \Redis::OPT_READ_TIMEOUT => -1,
            \Redis::OPT_SERIALIZER => \Redis::SERIALIZER_JSON,
        ]
    ],
    
    // Cache connection
    'cache' => [
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => getenv('REDIS_PORT') ?: 6379,
        'auth' => getenv('REDIS_PASSWORD') ?: '',
        'db' => 1,
        'prefix' => 'cache:',
        'expire' => 3600,
    ],
    
    // Queue connection
    'queue' => [
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => getenv('REDIS_PORT') ?: 6379,
        'auth' => getenv('REDIS_PASSWORD') ?: '',
        'db' => 2,
        'prefix' => 'queue:',
    ],
    
    // Progress tracking
    'progress' => [
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => getenv('REDIS_PORT') ?: 6379,
        'auth' => getenv('REDIS_PASSWORD') ?: '',
        'db' => 3,
        'prefix' => 'progress:',
        'expire' => 86400, // 24 hours
    ],
];