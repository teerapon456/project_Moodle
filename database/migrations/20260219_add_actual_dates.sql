-- Migration: Add actual dates to ya_milestones
-- Date: 2026-02-19

ALTER TABLE `ya_milestones` 
ADD COLUMN `actual_start_date` datetime DEFAULT NULL AFTER `due_date`,
ADD COLUMN `actual_end_date` datetime DEFAULT NULL AFTER `actual_start_date`;
