-- Add emp_type column to iga_tests for distinguishing employee vs applicant tests
-- emp_type: 'all' (default), 'employee', 'applicant'
ALTER TABLE iga_tests
    ADD COLUMN `emp_type` ENUM('all','employee','applicant') NOT NULL DEFAULT 'all' COMMENT 'Target emp type: employee, applicant, or all'
    AFTER `role_id`;
