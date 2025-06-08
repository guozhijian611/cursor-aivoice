<?php

namespace app\controller;

use Workerman\Connection\TcpConnection;
use support\Request;
use app\infrastructure\Service\ProgressService;
use support\Log;

class WebSocketController
{
    private array $taskSubscriptions = [];
    private ProgressService $progressService;
    
    public function __construct()
    {
        $this->progressService = new ProgressService();
    }
    
    /**
     * WebSocket connection open
     */
    public function onOpen(TcpConnection $connection, Request $request)
    {
        $connection->uid = uniqid();
        Log::info("WebSocket connection opened: {$connection->uid}");
        
        // Send welcome message
        $connection->send(json_encode([
            'type' => 'connected',
            'connection_id' => $connection->uid,
            'message' => 'Connected to progress tracking'
        ]));
    }
    
    /**
     * WebSocket message received
     */
    public function onMessage(TcpConnection $connection, $data)
    {
        try {
            $message = json_decode($data, true);
            
            if (!$message || !isset($message['action'])) {
                $connection->send(json_encode([
                    'type' => 'error',
                    'message' => 'Invalid message format'
                ]));
                return;
            }
            
            switch ($message['action']) {
                case 'subscribe':
                    $this->handleSubscribe($connection, $message);
                    break;
                    
                case 'unsubscribe':
                    $this->handleUnsubscribe($connection, $message);
                    break;
                    
                case 'get_progress':
                    $this->handleGetProgress($connection, $message);
                    break;
                    
                case 'ping':
                    $connection->send(json_encode(['type' => 'pong']));
                    break;
                    
                default:
                    $connection->send(json_encode([
                        'type' => 'error',
                        'message' => 'Unknown action'
                    ]));
            }
            
        } catch (\Exception $e) {
            Log::error("WebSocket error: " . $e->getMessage());
            $connection->send(json_encode([
                'type' => 'error',
                'message' => 'Internal server error'
            ]));
        }
    }
    
    /**
     * WebSocket connection closed
     */
    public function onClose(TcpConnection $connection)
    {
        // Clean up subscriptions
        if (isset($connection->uid)) {
            unset($this->taskSubscriptions[$connection->uid]);
            Log::info("WebSocket connection closed: {$connection->uid}");
        }
    }
    
    /**
     * Handle task subscription
     */
    private function handleSubscribe(TcpConnection $connection, array $message): void
    {
        if (!isset($message['task_id'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => 'Task ID required for subscription'
            ]));
            return;
        }
        
        $taskId = (int)$message['task_id'];
        
        // Store subscription
        if (!isset($this->taskSubscriptions[$connection->uid])) {
            $this->taskSubscriptions[$connection->uid] = [];
        }
        $this->taskSubscriptions[$connection->uid][] = $taskId;
        
        // Send current progress
        $progress = $this->progressService->getProgress($taskId);
        if ($progress) {
            $connection->send(json_encode([
                'type' => 'progress',
                'task_id' => $taskId,
                'data' => $progress
            ]));
        }
        
        $connection->send(json_encode([
            'type' => 'subscribed',
            'task_id' => $taskId,
            'message' => "Subscribed to task {$taskId} progress updates"
        ]));
        
        Log::info("Connection {$connection->uid} subscribed to task {$taskId}");
    }
    
    /**
     * Handle task unsubscription
     */
    private function handleUnsubscribe(TcpConnection $connection, array $message): void
    {
        if (!isset($message['task_id'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => 'Task ID required for unsubscription'
            ]));
            return;
        }
        
        $taskId = (int)$message['task_id'];
        
        // Remove subscription
        if (isset($this->taskSubscriptions[$connection->uid])) {
            $this->taskSubscriptions[$connection->uid] = array_filter(
                $this->taskSubscriptions[$connection->uid],
                fn($id) => $id !== $taskId
            );
        }
        
        $connection->send(json_encode([
            'type' => 'unsubscribed',
            'task_id' => $taskId,
            'message' => "Unsubscribed from task {$taskId} progress updates"
        ]));
        
        Log::info("Connection {$connection->uid} unsubscribed from task {$taskId}");
    }
    
    /**
     * Handle get progress request
     */
    private function handleGetProgress(TcpConnection $connection, array $message): void
    {
        if (!isset($message['task_id'])) {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => 'Task ID required'
            ]));
            return;
        }
        
        $taskId = (int)$message['task_id'];
        $progress = $this->progressService->getProgress($taskId);
        
        if ($progress) {
            $connection->send(json_encode([
                'type' => 'progress',
                'task_id' => $taskId,
                'data' => $progress
            ]));
        } else {
            $connection->send(json_encode([
                'type' => 'no_progress',
                'task_id' => $taskId,
                'message' => 'No progress data available'
            ]));
        }
    }
    
    /**
     * Broadcast progress update to subscribed connections
     * This would be called by a separate process monitoring Redis pub/sub
     */
    public function broadcastProgress(int $taskId, array $progress): void
    {
        foreach ($this->taskSubscriptions as $connectionId => $taskIds) {
            if (in_array($taskId, $taskIds)) {
                // In a real implementation, we'd need to maintain connection references
                // This is a simplified version
                Log::info("Would broadcast to connection {$connectionId} for task {$taskId}");
            }
        }
    }
}