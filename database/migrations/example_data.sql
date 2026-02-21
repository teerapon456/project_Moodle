-- Insert Example Calendar
INSERT INTO ya_calendars (name, description, year, owner_id, status) 
VALUES ('Strategy & Innovation 2026', 'Example calendar for tracking strategic initiatives and innovation projects.', 2026, 1, 'active');

SET @cal_id = LAST_INSERT_ID();

-- Insert Calendar Member (Owner)
INSERT INTO ya_calendar_members (calendar_id, user_id, role) 
VALUES (@cal_id, 1, 'owner');

-- Insert Example Activity
INSERT INTO ya_activities (calendar_id, name, objective, description, start_date, end_date, location, status, key_person_id, created_by) 
VALUES (@cal_id, 'Q1 Strategy Review', 'Analyze Q4 performance and internalize 2026 goals.', 'A comprehensive review meeting with all department heads.', '2026-03-01 09:00:00', '2026-03-31 17:00:00', 'HQ Meeting Room A', 'in_progress', 1, 1);

SET @act_id = LAST_INSERT_ID();

-- Insert Example Milestones
INSERT INTO ya_milestones (activity_id, name, description, start_date, due_date, status, weight_percent, order_index) 
VALUES (@act_id, 'Data Collection', 'Gather KPIs and financial results from all departments.', '2026-03-01 08:00:00', '2026-03-10 17:00:00', 'completed', 30, 1);

INSERT INTO ya_milestones (activity_id, name, description, start_date, due_date, status, weight_percent, order_index) 
VALUES (@act_id, 'Draft Report', 'Create the initial strategy review document.', '2026-03-11 08:00:00', '2026-03-20 17:00:00', 'in_progress', 40, 2);

INSERT INTO ya_milestones (activity_id, name, description, start_date, due_date, status, weight_percent, order_index) 
VALUES (@act_id, 'Final Presentation', 'Present findings to the board.', '2026-03-25 08:00:00', '2026-03-31 15:00:00', 'pending', 30, 3);
