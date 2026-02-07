<?php
// Modules/YearlyActivity/seed_data.php

$db = new \Database();
$conn = $db->getConnection();

echo "<h1>Seeding Data...</h1>";

try {
    $conn->beginTransaction();

    // 1. Create Calendars
    // User 8: Strategic Plan
    $conn->exec("INSERT INTO ya_calendars (name, year, owner_id) VALUES ('Strategic Plan 2026', 2026, 8)");
    $cal8_id = $conn->lastInsertId();
    echo "<p>Created Calendar for User 8 (ID: $cal8_id)</p>";

    // User 6: Training Schedule
    $conn->exec("INSERT INTO ya_calendars (name, year, owner_id) VALUES ('Training Schedule 2026', 2026, 6)");
    $cal6_id = $conn->lastInsertId();
    echo "<p>Created Calendar for User 6 (ID: $cal6_id)</p>";

    // 2. Add Members (Cross-member)
    // User 6 is editor in User 8's calendar
    $conn->exec("INSERT INTO ya_calendar_members (calendar_id, user_id, role) VALUES ($cal8_id, 6, 'editor')");
    // User 8 is viewer in User 6's calendar
    $conn->exec("INSERT INTO ya_calendar_members (calendar_id, user_id, role) VALUES ($cal6_id, 8, 'viewer')");

    // 3. create Activities
    // Activity 1 for User 8
    $sqlAct = "INSERT INTO ya_activities (calendar_id, name, type, objective, description, scope, status, start_date, end_date, location, key_person_id, created_by) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmtAct = $conn->prepare($sqlAct);

    $stmtAct->execute([$cal8_id, 'Annual Financial Audit', 'Audit', 'Ensure compliance', 'Full audit of 2025 records', 'Finance Dept', 'planned', '2026-03-01', '2026-03-15', 'HQ Meeting Room', 8, 8]);
    $act1_id = $conn->lastInsertId();
    echo "<p>Created Activity 'Annual Audit' (ID: $act1_id)</p>";

    // Activity 2 for User 8
    $stmtAct->execute([$cal8_id, 'Q1 Team Building', 'Event', 'Boost morale', 'Outing at the beach', 'All Staff', 'proposed', '2026-04-10', '2026-04-12', 'Rayong Resort', 6, 8]);
    $act2_id = $conn->lastInsertId();

    // Activity 3 for User 6
    $stmtAct->execute([$cal6_id, 'Python Workshop', 'Training', 'Upskill team', 'Basic Python for Data Analysis', 'IT & HR', 'in_progress', '2026-01-25', '2026-01-30', 'Training Room A', 6, 6]);
    $act3_id = $conn->lastInsertId();

    // 4. Milestones
    $sqlMile = "INSERT INTO ya_milestones (activity_id, name, description, due_date, status, weight_percent) VALUES (?, ?, ?, ?, ?, ?)";
    $stmtMile = $conn->prepare($sqlMile);

    // Milestones for Audit
    $stmtMile->execute([$act1_id, 'Prepare Documents', 'Gather all invoices', '2026-02-28', 'pending', 30]);
    $mile1_id = $conn->lastInsertId();
    $stmtMile->execute([$act1_id, 'External Auditor Review', 'On-site visit', '2026-03-10', 'pending', 50]);

    // Milestones for Workshop
    $stmtMile->execute([$act3_id, 'Setup Environment', 'Install Python/VSCode', '2026-01-25', 'completed', 20]);
    $mile3_id = $conn->lastInsertId();
    $stmtMile->execute([$act3_id, 'Day 1: Basics', 'Variables, Loops', '2026-01-26', 'completed', 20]);
    $stmtMile->execute([$act3_id, 'Day 2: Pandas', 'Dataframes', '2026-01-27', 'in_progress', 20]);

    // 5. RASCI
    $sqlRasci = "INSERT INTO ya_milestone_rasci (milestone_id, user_id, role) VALUES (?, ?, ?)";
    $stmtRasci = $conn->prepare($sqlRasci);

    // Audit RASCI
    $stmtRasci->execute([$mile1_id, 8, 'R']); // User 8 Responsible
    $stmtRasci->execute([$mile1_id, 6, 'S']); // User 6 Support

    // Workshop RASCI
    $stmtRasci->execute([$mile3_id, 6, 'R']);

    // 6. Risks
    $conn->exec("INSERT INTO ya_milestone_risks (milestone_id, risk_description, impact, probability, mitigation_plan) 
                 VALUES ($mile1_id, 'Missing Invoices', 4, 3, 'Contact vendors early')");

    $conn->commit();
    echo "<h1>Seeding Complete!</h1><a href='?page=dashboard'>Go to Dashboard</a>";
} catch (Exception $e) {
    $conn->rollBack();
    echo "<h1>Error Seeding Data</h1><p>" . $e->getMessage() . "</p>";
}
