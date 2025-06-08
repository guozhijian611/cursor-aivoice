<?php

require_once __DIR__ . '/../vendor/autoload.php';

use think\facade\Db;
use support\bootstrap\ThinkOrm;

// Load configuration
$config = require __DIR__ . '/../config/thinkorm.php';
Db::setConfig($config);

// Initialize ThinkORM
ThinkOrm::start(null);

echo "Running database migrations...\n";

// Get all migration files
$migrations = glob(__DIR__ . '/migrations/*.php');
sort($migrations);

foreach ($migrations as $migration) {
    $filename = basename($migration);
    echo "Running migration: $filename\n";
    
    try {
        require $migration;
        echo "✓ Success\n";
    } catch (\Exception $e) {
        echo "✗ Failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "\nAll migrations completed successfully!\n";