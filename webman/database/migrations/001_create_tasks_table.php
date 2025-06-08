<?php

use think\facade\Db;

// Create tasks table
$sql = "CREATE TABLE IF NOT EXISTS `tasks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_number` VARCHAR(50) NOT NULL COMMENT 'Task number format: UserID_YYYYMMDD_Serial',
    `user_id` INT UNSIGNED NOT NULL COMMENT 'User ID',
    `status` ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    `process_type` ENUM('audio_extract', 'denoise', 'fast_recognition', 'transcription', 'full_process') NOT NULL,
    `priority` TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT 'Priority: 8=extract, 6=denoise, 4=recognition, 2=transcription',
    `total_files` INT UNSIGNED NOT NULL DEFAULT 0,
    `processed_files` INT UNSIGNED NOT NULL DEFAULT 0,
    `current_step` VARCHAR(50) NULL COMMENT 'Current processing step',
    `progress` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Progress percentage',
    `result_data` JSON NULL COMMENT 'Final result data',
    `error_message` TEXT NULL COMMENT 'Error message if failed',
    `retry_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `started_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_task_number` (`task_number`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_priority_status` (`priority`, `status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Task main table'";

Db::execute($sql);