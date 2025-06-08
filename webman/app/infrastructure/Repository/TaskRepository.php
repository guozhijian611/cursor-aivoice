<?php

namespace app\infrastructure\Repository;

use app\domain\Task\Task;
use app\domain\Task\ValueObject\TaskStatus;
use think\db\exception\DbException;

class TaskRepository
{
    /**
     * Find task by ID
     */
    public function findById(int $id): ?Task
    {
        return Task::find($id);
    }
    
    /**
     * Find task by task number
     */
    public function findByTaskNumber(string $taskNumber): ?Task
    {
        return Task::where('task_number', $taskNumber)->find();
    }
    
    /**
     * Get tasks by user ID with pagination
     */
    public function getByUserId(int $userId, int $page = 1, int $pageSize = 20): array
    {
        $tasks = Task::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->paginate([
                'page' => $page,
                'list_rows' => $pageSize
            ]);
            
        return [
            'items' => $tasks->items(),
            'total' => $tasks->total(),
            'page' => $tasks->currentPage(),
            'pageSize' => $pageSize,
            'totalPages' => $tasks->lastPage()
        ];
    }
    
    /**
     * Get pending tasks for processing
     */
    public function getPendingTasks(int $limit = 10): array
    {
        return Task::where('status', TaskStatus::PENDING)
            ->where('retry_count', '<', 3)
            ->order('priority', 'desc')
            ->order('created_at', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
    
    /**
     * Get failed tasks for retry
     */
    public function getFailedTasksForRetry(int $minutesAgo = 5, int $limit = 10): array
    {
        $timeThreshold = date('Y-m-d H:i:s', time() - ($minutesAgo * 60));
        
        return Task::where('status', TaskStatus::FAILED)
            ->where('retry_count', '<', 3)
            ->where('updated_at', '<=', $timeThreshold)
            ->order('priority', 'desc')
            ->order('updated_at', 'asc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
    
    /**
     * Create new task
     */
    public function create(array $data): Task
    {
        $task = new Task();
        
        // Generate task number if not provided
        if (empty($data['task_number']) && !empty($data['user_id'])) {
            $data['task_number'] = Task::generateTaskNumber($data['user_id']);
        }
        
        $task->save($data);
        return $task;
    }
    
    /**
     * Update task
     */
    public function update(int $id, array $data): bool
    {
        $task = $this->findById($id);
        if (!$task) {
            return false;
        }
        
        return $task->save($data);
    }
    
    /**
     * Get task statistics by user
     */
    public function getUserStatistics(int $userId): array
    {
        $stats = Task::where('user_id', $userId)
            ->field('status, COUNT(*) as count')
            ->group('status')
            ->select()
            ->toArray();
            
        $result = [
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'completed' => 0,
            'failed' => 0,
            'cancelled' => 0,
        ];
        
        foreach ($stats as $stat) {
            $result[$stat['status']] = $stat['count'];
            $result['total'] += $stat['count'];
        }
        
        return $result;
    }
    
    /**
     * Check if user has reached task limit
     */
    public function hasReachedDailyLimit(int $userId, int $dailyLimit = 100): bool
    {
        $today = date('Y-m-d');
        $count = Task::where('user_id', $userId)
            ->whereTime('created_at', '>=', $today)
            ->count();
            
        return $count >= $dailyLimit;
    }
}