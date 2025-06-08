<?php

namespace support\bootstrap;

use Webman\Bootstrap;
use think\facade\Db;
use support\Log;

class ThinkOrm implements Bootstrap
{
    public static function start($worker)
    {
        $config = config('thinkorm');
        if (!$config) {
            return;
        }
        
        // Initialize ThinkORM
        Db::setConfig($config);
        
        // Set event callback for SQL logging in debug mode
        if ($config['connections'][$config['default']]['trigger_sql'] ?? false) {
            Db::listen(function ($sql, $time, $master) {
                Log::debug('[SQL] ' . $sql . ' [' . $time . 's]');
            });
        }
        
        // Initialize Redis cache if configured
        if (isset($config['cache'])) {
            \think\facade\Cache::config($config['cache']);
        }
    }
}