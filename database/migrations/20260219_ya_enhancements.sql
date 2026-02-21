-- Migration: Add attachments and comments for YearlyActivity
-- Date: 2026-02-19

CREATE TABLE IF NOT EXISTS `ya_milestone_attachments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `milestone_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `uploaded_by` int UNSIGNED DEFAULT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ya_attachment_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `ya_milestones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ya_activity_comments` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `activity_id` int NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_ya_comment_activity` FOREIGN KEY (`activity_id`) REFERENCES `ya_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
