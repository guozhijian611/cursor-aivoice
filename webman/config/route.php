<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;

// API Routes for Task Management
Route::group('/api', function () {
    // Task endpoints
    Route::get('/tasks/statistics', [app\controller\TaskController::class, 'statistics']);
    Route::get('/tasks', [app\controller\TaskController::class, 'index']);
    Route::post('/tasks', [app\controller\TaskController::class, 'create']);
    Route::get('/tasks/{taskNumber}', [app\controller\TaskController::class, 'show']);
    Route::post('/tasks/{taskNumber}/cancel', [app\controller\TaskController::class, 'cancel']);
    Route::post('/tasks/{taskNumber}/retry', [app\controller\TaskController::class, 'retry']);
    
    // Health check
    Route::get('/health', function () {
        return json([
            'status' => 'healthy',
            'timestamp' => time(),
            'services' => [
                'database' => true,
                'redis' => true,
                'rabbitmq' => true,
            ]
        ]);
    });
});

// Default routes
Route::get('/', function ($request) {
    return response('Welcome to Audio Processing API');
});

Route::fallback(function(){
    return json(['code' => 404, 'msg' => 'Not Found'], 404);
});






