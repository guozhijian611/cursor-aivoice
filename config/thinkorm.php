<?php

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            // Database type
            'type'            => 'mysql',
            // Server address
            'hostname'        => env('DB_HOST', '127.0.0.1'),
            // Database name
            'database'        => env('DB_DATABASE', 'audio_processing'),
            // Username
            'username'        => env('DB_USERNAME', 'root'),
            // Password
            'password'        => env('DB_PASSWORD', ''),
            // Port
            'hostport'        => env('DB_PORT', '3306'),
            // Connection params
            'params'          => [],
            // Database charset
            'charset'         => 'utf8mb4',
            // Database table prefix
            'prefix'          => '',
            // Database deploy type: 0 centralized (single server), 1 distributed (master/slave server)
            'deploy'          => 0,
            // Database read/write separation for master/slave valid
            'rw_separate'     => false,
            // Number of master servers after read/write separation
            'master_num'      => 1,
            // Specify slave server serial number
            'slave_no'        => '',
            // Check if data field is strictly present
            'fields_strict'   => true,
            // Auto write timestamp field
            'auto_timestamp'  => true,
            // Timestamp field names
            'create_time'     => 'created_at',
            'update_time'     => 'updated_at',
            // SQL execution log
            'sql_explain'     => env('APP_DEBUG', false),
            // Builder class
            'builder'         => '',
            // Query class
            'query'           => '',
            // Whether to enable SQL monitoring
            'trigger_sql'     => env('APP_DEBUG', false),
        ],
    ],
    
    // Custom cache configuration for Redis
    'cache' => [
        'type'   => 'redis',
        'host'   => env('REDIS_HOST', '127.0.0.1'),
        'port'   => env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD', ''),
        'select' => 0,
        'timeout' => 0,
        'expire' => 3600,
        'persistent' => false,
        'prefix' => 'think_',
    ],
];