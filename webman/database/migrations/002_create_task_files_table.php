<?php

use think\facade\Db;

// Create task_files table
$sql = "CREATE TABLE IF NOT EXISTS `task_files` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_id` INT UNSIGNED NOT NULL,
    `original_filename` VARCHAR(255) NOT NULL,
    `stored_path` VARCHAR(500) NOT NULL COMMENT 'Stored file path',
    `file_type` ENUM('video', 'audio') NOT NULL,
    `file_size` BIGINT UNSIGNED NOT NULL COMMENT 'File size in bytes',
    `mime_type` VARCHAR(100) NOT NULL,
    `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    `processing_step` VARCHAR(50) NULL COMMENT 'Current processing step',
    `processed_path` VARCHAR(500) NULL COMMENT 'Processed file path',
    `error_message` TEXT NULL,
    `metadata` JSON NULL COMMENT 'File metadata',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_task_id` (`task_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_task_files_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Task file information'";

Db::execute($sql);