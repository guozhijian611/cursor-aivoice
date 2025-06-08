<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\application\Service\TaskService;
use app\infrastructure\Repository\TaskRepository;

class TaskController
{
    private TaskService $taskService;
    private TaskRepository $taskRepository;
    
    public function __construct()
    {
        $this->taskService = new TaskService();
        $this->taskRepository = new TaskRepository();
    }
    
    /**
     * Create new task
     * POST /api/tasks
     */
    public function create(Request $request): Response
    {
        try {
            // Validate request
            $processType = $request->post('process_type');
            if (!$processType) {
                return json(['success' => false, 'message' => 'Process type is required'], 400);
            }
            
            // Get uploaded files
            $files = $request->file();
            if (empty($files)) {
                return json(['success' => false, 'message' => 'No files uploaded'], 400);
            }
            
            // Get user ID (in real app, this would come from authentication)
            $userId = $request->post('user_id', 1);
            
            // Process files array
            $processedFiles = [];
            foreach ($files as $file) {
                if (is_array($file)) {
                    foreach ($file as $f) {
                        $processedFiles[] = $f;
                    }
                } else {
                    $processedFiles[] = $file;
                }
            }
            
            // Create task
            $result = $this->taskService->createTask($userId, $processType, $processedFiles);
            
            if ($result['success']) {
                return json($result, 201);
            } else {
                return json($result, 400);
            }
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get task details
     * GET /api/tasks/{taskNumber}
     */
    public function show(Request $request, string $taskNumber): Response
    {
        try {
            // Get user ID (in real app, this would come from authentication)
            $userId = $request->get('user_id', 1);
            
            $task = $this->taskService->getTaskDetails($taskNumber, $userId);
            
            if (!$task) {
                return json(['success' => false, 'message' => 'Task not found'], 404);
            }
            
            return json(['success' => true, 'data' => $task]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get user's tasks
     * GET /api/tasks
     */
    public function index(Request $request): Response
    {
        try {
            // Get user ID (in real app, this would come from authentication)
            $userId = $request->get('user_id', 1);
            $page = (int)$request->get('page', 1);
            $pageSize = (int)$request->get('page_size', 20);
            
            $tasks = $this->taskRepository->getByUserId($userId, $page, $pageSize);
            
            return json(['success' => true, 'data' => $tasks]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Cancel task
     * POST /api/tasks/{taskNumber}/cancel
     */
    public function cancel(Request $request, string $taskNumber): Response
    {
        try {
            // Get user ID (in real app, this would come from authentication)
            $userId = $request->post('user_id', 1);
            
            $result = $this->taskService->cancelTask($taskNumber, $userId);
            
            if ($result) {
                return json(['success' => true, 'message' => 'Task cancelled successfully']);
            } else {
                return json(['success' => false, 'message' => 'Failed to cancel task'], 400);
            }
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Retry failed task
     * POST /api/tasks/{taskNumber}/retry
     */
    public function retry(Request $request, string $taskNumber): Response
    {
        try {
            // Get user ID (in real app, this would come from authentication)
            $userId = $request->post('user_id', 1);
            
            $result = $this->taskService->retryTask($taskNumber, $userId);
            
            if ($result) {
                return json(['success' => true, 'message' => 'Task retry initiated']);
            } else {
                return json(['success' => false, 'message' => 'Failed to retry task'], 400);
            }
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get user statistics
     * GET /api/tasks/statistics
     */
    public function statistics(Request $request): Response
    {
        try {
            // Get user ID (in real app, this would come from authentication)
            $userId = $request->get('user_id', 1);
            
            $stats = $this->taskRepository->getUserStatistics($userId);
            
            return json(['success' => true, 'data' => $stats]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }
}