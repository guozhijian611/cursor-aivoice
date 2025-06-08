<?php

use think\facade\Db;

// Create processing_results table
$sql = "CREATE TABLE IF NOT EXISTS `processing_results` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `task_id` INT UNSIGNED NOT NULL,
    `task_file_id` INT UNSIGNED NOT NULL,
    `result_type` ENUM('audio_extraction', 'denoising', 'recognition', 'transcription') NOT NULL,
    `result_path` VARCHAR(500) NULL COMMENT 'Result file path',
    `result_data` JSON NULL COMMENT 'Result data (e.g., transcription text)',
    `file_size` BIGINT UNSIGNED NULL COMMENT 'Result file size',
    `duration` DECIMAL(10,2) NULL COMMENT 'Audio/video duration in seconds',
    `metadata` JSON NULL COMMENT 'Additional metadata',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_task_id` (`task_id`),
    KEY `idx_task_file_id` (`task_file_id`),
    KEY `idx_result_type` (`result_type`),
    CONSTRAINT `fk_results_task_id` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_results_task_file_id` FOREIGN KEY (`task_file_id`) REFERENCES `task_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Processing result storage'";

Db::execute($sql);