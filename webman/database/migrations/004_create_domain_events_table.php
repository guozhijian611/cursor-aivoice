<?php

use think\facade\Db;

// Create domain_events table
$sql = "CREATE TABLE IF NOT EXISTS `domain_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `aggregate_id` VARCHAR(100) NOT NULL COMMENT 'Aggregate root ID',
    `aggregate_type` VARCHAR(100) NOT NULL COMMENT 'Aggregate type',
    `event_type` VARCHAR(100) NOT NULL COMMENT 'Event type',
    `event_data` JSON NOT NULL COMMENT 'Event payload',
    `user_id` INT UNSIGNED NULL COMMENT 'User who triggered the event',
    `occurred_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the event occurred',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_aggregate` (`aggregate_id`, `aggregate_type`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_occurred_at` (`occurred_at`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Domain event store'";

Db::execute($sql);