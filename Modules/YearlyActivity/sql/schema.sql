-- Yearly Activity Calendar Module Schema
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `ya_milestone_risks`;

DROP TABLE IF EXISTS `ya_milestone_resources`;

DROP TABLE IF EXISTS `ya_milestone_rasci`;

DROP TABLE IF EXISTS `ya_milestones`;

DROP TABLE IF EXISTS `ya_activities`;

DROP TABLE IF EXISTS `ya_calendar_members`;

DROP TABLE IF EXISTS `ya_calendars`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Calendars
CREATE TABLE IF NOT EXISTS `ya_calendars` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `year` YEAR NOT NULL,
    `owner_id` INT NOT NULL,
    `status` ENUM('active', 'archived') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ya_calendars_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 2. Calendar Members (Permissions)
CREATE TABLE IF NOT EXISTS `ya_calendar_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `calendar_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `role` ENUM(
        'owner',
        'admin',
        'editor',
        'viewer'
    ) NOT NULL DEFAULT 'viewer',
    `joined_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_calendar_user` (`calendar_id`, `user_id`),
    CONSTRAINT `fk_ya_members_calendar` FOREIGN KEY (`calendar_id`) REFERENCES `ya_calendars` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ya_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 3. Activities
CREATE TABLE IF NOT EXISTS `ya_activities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `calendar_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `type` VARCHAR(100) NULL,
    `objective` TEXT NULL,
    `description` TEXT NULL,
    `scope` TEXT NULL,
    `status` ENUM(
        'proposed',
        'planned',
        'incoming',
        'in_progress',
        'on_hold',
        'completed',
        'cancelled'
    ) DEFAULT 'planned',
    `start_date` DATETIME NULL,
    `end_date` DATETIME NULL,
    `location` VARCHAR(255) NULL,
    `key_person_id` INT NULL,
    `created_by` INT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ya_activities_calendar` FOREIGN KEY (`calendar_id`) REFERENCES `ya_calendars` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ya_activities_key_person` FOREIGN KEY (`key_person_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ya_activities_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 4. Milestones
CREATE TABLE IF NOT EXISTS `ya_milestones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `activity_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `due_date` DATE NULL,
    `status` ENUM(
        'pending',
        'in_progress',
        'completed',
        'cancelled'
    ) DEFAULT 'pending',
    `weight_percent` INT DEFAULT 0,
    `order_index` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ya_milestones_activity` FOREIGN KEY (`activity_id`) REFERENCES `ya_activities` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 5. RASCI (Linked to Milestone)
CREATE TABLE IF NOT EXISTS `ya_milestone_rasci` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `milestone_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `role` ENUM('R', 'A', 'S', 'C', 'I') NOT NULL,
    CONSTRAINT `fk_ya_rasci_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `ya_milestones` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ya_rasci_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 6. Resources (Linked to Milestone)
CREATE TABLE IF NOT EXISTS `ya_milestone_resources` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `milestone_id` INT NOT NULL,
    `resource_name` VARCHAR(255) NOT NULL,
    `quantity` INT DEFAULT 1,
    `unit_cost` DECIMAL(10, 2) DEFAULT 0.00,
    `unit` VARCHAR(50) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ya_resources_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `ya_milestones` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 7. Risks (Linked to Milestone)
CREATE TABLE IF NOT EXISTS `ya_milestone_risks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `milestone_id` INT NOT NULL,
    `risk_description` TEXT NOT NULL,
    `impact` INT COMMENT '1-5',
    `probability` INT COMMENT '1-5',
    `mitigation_plan` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ya_risks_milestone` FOREIGN KEY (`milestone_id`) REFERENCES `ya_milestones` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- 8. Activity Status Logs (Timeline History)
CREATE TABLE IF NOT EXISTS `ya_activity_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `activity_id` INT NOT NULL,
    `previous_status` VARCHAR(50),
    `new_status` VARCHAR(50),
    `note` TEXT NULL,
    `changed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `changed_by` INT NULL,
    CONSTRAINT `fk_ya_logs_activity` FOREIGN KEY (`activity_id`) REFERENCES `ya_activities` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Update Tables with Status if not exists (handled by migration script, but defined here)
-- ALTER TABLE ya_calendars ADD COLUMN status ENUM('active', 'archived') DEFAULT 'active';
-- ALTER TABLE ya_activities ADD COLUMN status ENUM('proposed', 'planned', 'incoming', 'in_progress', 'on_hold', 'completed', 'cancelled') DEFAULT 'planned';