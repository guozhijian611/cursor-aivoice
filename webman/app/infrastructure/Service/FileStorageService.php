<?php

namespace app\infrastructure\Service;

use Webman\Http\UploadFile;

class FileStorageService
{
    private string $storagePath;
    private array $allowedExtensions;
    private int $maxFileSize;
    
    public function __construct()
    {
        $this->storagePath = base_path() . '/storage/uploads';
        $this->allowedExtensions = [
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv', 'webm'],
            'audio' => ['mp3', 'wav', 'flac', 'aac', 'ogg', 'wma', 'm4a'],
        ];
        $this->maxFileSize = 500 * 1024 * 1024; // 500MB
        
        // Create storage directory if not exists
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }
    
    /**
     * Store uploaded file
     */
    public function store($file, string $taskNumber): string
    {
        if ($file instanceof UploadFile) {
            return $this->storeUploadFile($file, $taskNumber);
        } elseif (is_array($file)) {
            return $this->storeArrayFile($file, $taskNumber);
        }
        
        throw new \InvalidArgumentException('Invalid file type');
    }
    
    /**
     * Store WebMan UploadFile
     */
    private function storeUploadFile(UploadFile $file, string $taskNumber): string
    {
        // Validate file
        $this->validateFile($file->getUploadName(), $file->getSize());
        
        // Create task directory
        $taskDir = $this->storagePath . '/' . $taskNumber;
        if (!is_dir($taskDir)) {
            mkdir($taskDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file->getUploadName(), PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $destination = $taskDir . '/' . $filename;
        
        // Move file
        $file->move($destination);
        
        return 'storage/uploads/' . $taskNumber . '/' . $filename;
    }
    
    /**
     * Store array file data (for testing/API)
     */
    private function storeArrayFile(array $file, string $taskNumber): string
    {
        if (!isset($file['name'], $file['tmp_name'], $file['size'])) {
            throw new \InvalidArgumentException('Invalid file array structure');
        }
        
        // Validate file
        $this->validateFile($file['name'], $file['size']);
        
        // Create task directory
        $taskDir = $this->storagePath . '/' . $taskNumber;
        if (!is_dir($taskDir)) {
            mkdir($taskDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $destination = $taskDir . '/' . $filename;
        
        // Move file
        if (isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            move_uploaded_file($file['tmp_name'], $destination);
        } elseif (isset($file['content'])) {
            file_put_contents($destination, $file['content']);
        } else {
            throw new \RuntimeException('Cannot store file');
        }
        
        return 'storage/uploads/' . $taskNumber . '/' . $filename;
    }
    
    /**
     * Validate file
     */
    private function validateFile(string $filename, int $size): void
    {
        // Check file size
        if ($size > $this->maxFileSize) {
            throw new \InvalidArgumentException('File size exceeds maximum allowed size of ' . ($this->maxFileSize / 1024 / 1024) . 'MB');
        }
        
        // Check extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = array_merge($this->allowedExtensions['video'], $this->allowedExtensions['audio']);
        
        if (!in_array($extension, $allowed)) {
            throw new \InvalidArgumentException('File type not allowed. Allowed types: ' . implode(', ', $allowed));
        }
    }
    
    /**
     * Delete file
     */
    public function delete(string $path): bool
    {
        $fullPath = base_path() . '/' . $path;
        
        if (file_exists($fullPath) && is_file($fullPath)) {
            return unlink($fullPath);
        }
        
        return false;
    }
    
    /**
     * Delete task directory
     */
    public function deleteTaskDirectory(string $taskNumber): bool
    {
        $taskDir = $this->storagePath . '/' . $taskNumber;
        
        if (!is_dir($taskDir)) {
            return false;
        }
        
        // Delete all files in directory
        $files = glob($taskDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Remove directory
        return rmdir($taskDir);
    }
    
    /**
     * Get file info
     */
    public function getFileInfo(string $path): ?array
    {
        $fullPath = base_path() . '/' . $path;
        
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            return null;
        }
        
        return [
            'path' => $path,
            'size' => filesize($fullPath),
            'mime_type' => mime_content_type($fullPath),
            'modified_at' => filemtime($fullPath),
            'extension' => pathinfo($fullPath, PATHINFO_EXTENSION),
        ];
    }
    
    /**
     * Get storage usage for a task
     */
    public function getTaskStorageUsage(string $taskNumber): int
    {
        $taskDir = $this->storagePath . '/' . $taskNumber;
        
        if (!is_dir($taskDir)) {
            return 0;
        }
        
        $totalSize = 0;
        $files = glob($taskDir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
            }
        }
        
        return $totalSize;
    }
}